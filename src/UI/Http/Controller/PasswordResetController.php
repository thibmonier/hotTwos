<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\User\PasswordResetMailer;
use App\Domain\User\User;
use App\Domain\User\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

/**
 * US-068 (T-068-06) — flux « mot de passe oublié » (demande → e-mail → réinitialisation).
 *
 * Routes **anonymes** (jamais couvertes par `access_control`). Anti-énumération (CA-3) : la page de
 * demande renvoie toujours le même écran de confirmation, que l'e-mail existe ou non. L'e-mail n'étant
 * unique **que par tenant**, un même e-mail peut porter plusieurs comptes : un jeton et un message sont
 * émis **par compte** (chaque lien cible un utilisateur précis). Nouveau mot de passe haché en Argon2id.
 */
final class PasswordResetController extends AbstractController
{
    use ResetPasswordControllerTrait;

    private const int MIN_PASSWORD_LENGTH = 12;
    /** Borne haute : au-delà, le hasher Symfony lève (mishandling exceptional conditions, règle 11 §7). */
    private const int MAX_PASSWORD_LENGTH = 128;

    public function __construct(
        private readonly ResetPasswordHelperInterface $resetPasswordHelper,
        private readonly UserRepository $users,
        private readonly PasswordResetMailer $mailer,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly EntityManagerInterface $em,
        private readonly RateLimiterFactoryInterface $passwordResetRequestLimiter,
    ) {
    }

    #[Route('/mot-de-passe-oublie', name: 'forgot_password_request', methods: ['GET', 'POST'])]
    public function request(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('reset_password_request', (string) $request->request->get('_csrf_token'))) {
                $this->addFlash('error', 'Jeton de sécurité invalide, veuillez réessayer.');

                return $this->redirectToRoute('forgot_password_request');
            }

            // Anti-abus par IP (T-068-10) : borne le nombre de demandes indépendamment du compte visé.
            if (!$this->passwordResetRequestLimiter->create($request->getClientIp())->consume()->isAccepted()) {
                $this->addFlash('error', 'Trop de demandes de réinitialisation. Réessayez dans quelques minutes.');

                return $this->redirectToRoute('forgot_password_request');
            }

            $this->sendResetLinks(trim((string) $request->request->get('email')));

            return $this->redirectToRoute('check_email');
        }

        return $this->render('security/forgot_password_request.html.twig');
    }

    #[Route('/mot-de-passe-oublie/verification', name: 'check_email', methods: ['GET'])]
    public function checkEmail(): Response
    {
        // Durée de vie affichée à titre indicatif ; aucun lien avec un compte précis (anti-énumération).
        return $this->render('security/check_email.html.twig', [
            'tokenLifetime' => $this->resetPasswordHelper->getTokenLifetime(),
        ]);
    }

    #[Route('/reinitialiser/{token}', name: 'reset_password', methods: ['GET', 'POST'])]
    public function reset(Request $request, ?string $token = null): Response
    {
        if (null !== $token) {
            // Sort le jeton de l'URL (évite fuite via historique/referer) : stocké en session, puis redirige.
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('reset_password');
        }

        $token = $this->getTokenFromSession();
        if (null === $token) {
            $this->addFlash('error', 'Lien de réinitialisation invalide ou expiré.');

            return $this->redirectToRoute('forgot_password_request');
        }

        try {
            /** @var User $user */
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface) {
            // Message générique : ne révèle pas la cause exacte (expiré / falsifié / déjà utilisé).
            $this->addFlash('error', 'Lien de réinitialisation invalide ou expiré. Refaites une demande.');

            return $this->redirectToRoute('forgot_password_request');
        }

        if ($request->isMethod('POST')) {
            return $this->handleNewPassword($request, $token, $user);
        }

        return $this->render('security/reset_password.html.twig');
    }

    private function sendResetLinks(string $email): void
    {
        // Garde de format : évite des lookups inutiles ; l'appelant redirige toujours vers le même
        // écran (anti-énumération), donc aucun retour d'erreur ici.
        if (false === filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            return;
        }

        // Un jeton et un e-mail par compte portant cette adresse (multi-tenant). Les échecs
        // (throttling, etc.) sont silencieux pour ne pas divulguer l'existence d'un compte (CA-3).
        foreach ($this->users->findByEmail($email) as $user) {
            try {
                $token = $this->resetPasswordHelper->generateResetToken($user);
            } catch (ResetPasswordExceptionInterface) {
                continue;
            }

            $url = $this->generateUrl('reset_password', ['token' => $token->getToken()], UrlGeneratorInterface::ABSOLUTE_URL);
            $this->mailer->sendResetLink($user, $url, $token->getExpiresAt());
        }
    }

    private function handleNewPassword(Request $request, string $token, User $user): Response
    {
        if (!$this->isCsrfTokenValid('reset_password', (string) $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide, veuillez réessayer.');

            return $this->render('security/reset_password.html.twig');
        }

        $new = (string) $request->request->get('new_password');
        $confirm = (string) $request->request->get('confirm_password');

        if (mb_strlen($new) < self::MIN_PASSWORD_LENGTH || mb_strlen($new) > self::MAX_PASSWORD_LENGTH) {
            $this->addFlash('error', sprintf('Le mot de passe doit contenir entre %d et %d caractères.', self::MIN_PASSWORD_LENGTH, self::MAX_PASSWORD_LENGTH));

            return $this->render('security/reset_password.html.twig');
        }
        if ($new !== $confirm) {
            $this->addFlash('error', 'Les deux mots de passe ne correspondent pas.');

            return $this->render('security/reset_password.html.twig');
        }

        // Jeton à usage unique : invalidé avant le changement effectif.
        $this->resetPasswordHelper->removeResetRequest($token);
        $user->changePassword($this->hasher->hashPassword($user, $new));
        $this->em->flush();
        $this->cleanSessionAfterReset();

        $this->addFlash('success', 'Votre mot de passe a été réinitialisé. Vous pouvez vous connecter.');

        return $this->redirectToRoute('login');
    }
}
