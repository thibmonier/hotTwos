<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Domain\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use InvalidArgumentException;

/**
 * US-067 (T-067-04) / US-068 (T-068-04) — écran « Mon compte » : l'utilisateur renseigne son nom/prénom
 * et change son mot de passe. Chaque action modifie **son propre** compte (#[CurrentUser]) ; les formulaires
 * sont protégés par CSRF, les mots de passe hachés en Argon2id, sans révélation d'information (CA-3).
 */
final class AccountController extends AbstractController
{
    private const int MIN_PASSWORD_LENGTH = 12;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
    }

    #[Route('/mon-compte', name: 'account', methods: ['GET'])]
    public function index(#[CurrentUser] User $user): Response
    {
        return $this->render('account/index.html.twig', ['account' => $user]);
    }

    #[Route('/mon-compte/profil', name: 'account_profile', methods: ['POST'])]
    public function updateProfile(#[CurrentUser] User $user, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('account_profile', (string) $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide, veuillez réessayer.');

            return $this->redirectToRoute('account');
        }

        $firstName = trim((string) $request->request->get('first_name'));
        $lastName = trim((string) $request->request->get('last_name'));

        try {
            $user->rename('' === $firstName ? null : $firstName, '' === $lastName ? null : $lastName);
        } catch (InvalidArgumentException) {
            $this->addFlash('error', 'Nom ou prénom invalide (100 caractères maximum).');

            return $this->redirectToRoute('account');
        }

        $this->em->flush();
        $this->addFlash('success', 'Votre profil a été mis à jour.');

        return $this->redirectToRoute('account');
    }

    #[Route('/mon-compte/mot-de-passe', name: 'account_password', methods: ['POST'])]
    public function changePassword(#[CurrentUser] User $user, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('account_password', (string) $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide, veuillez réessayer.');

            return $this->redirectToRoute('account');
        }

        $current = (string) $request->request->get('current_password');
        $new = (string) $request->request->get('new_password');
        $confirm = (string) $request->request->get('confirm_password');

        if (!$this->hasher->isPasswordValid($user, $current)) {
            // Message générique : ne révèle pas si l'échec vient du mot de passe actuel (CA-3).
            $this->addFlash('error', 'Impossible de changer le mot de passe : vérifiez vos saisies.');

            return $this->redirectToRoute('account');
        }
        if (mb_strlen($new) < self::MIN_PASSWORD_LENGTH) {
            $this->addFlash('error', sprintf('Le nouveau mot de passe doit contenir au moins %d caractères.', self::MIN_PASSWORD_LENGTH));

            return $this->redirectToRoute('account');
        }
        if ($new !== $confirm) {
            $this->addFlash('error', 'La confirmation ne correspond pas au nouveau mot de passe.');

            return $this->redirectToRoute('account');
        }

        $user->changePassword($this->hasher->hashPassword($user, $new));
        $this->em->flush();
        $this->addFlash('success', 'Votre mot de passe a été changé.');

        return $this->redirectToRoute('account');
    }
}
