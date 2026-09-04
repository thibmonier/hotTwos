<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Authorization\Authorizer;
use App\Application\Pricing\AssignProfile;
use App\Domain\Authorization\Permission;
use App\Domain\Pricing\PricingException;
use App\Domain\Shared\EffectivePeriod;
use App\Domain\User\User;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use InvalidArgumentException;

/**
 * US-060 (T-060-02) — action d'affectation d'un collaborateur à un profil de tarification (POST-Redirect-Get + CSRF).
 *
 * Sans affectation, {@see AssignProfile} n'est jamais déclenché et la valorisation reste `MISSING_RATE`
 * (finding F2 de la recette US-069). L'autorisation (MANAGE_PRICING) est vérifiée en premier — deny-by-default
 * (règle 11) — avant le jeton CSRF, pour qu'un collaborateur non habilité reçoive un 403 franc.
 */
final class ProfileAssignmentController extends AbstractController
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly AssignProfile $assignProfile,
    ) {
    }

    #[Route('/profils/affectations', name: 'pricing_assign', methods: ['POST'])]
    public function assign(#[CurrentUser] User $user, Request $request): RedirectResponse
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_PRICING);

        if (!$this->isCsrfTokenValid('pricing_assign', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide, veuillez réessayer.');

            return $this->redirectToRoute('pricing_admin');
        }

        $userId = trim((string) $request->request->get('userId'));
        $profileId = trim((string) $request->request->get('profileId'));
        $from = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $request->request->get('effectiveFrom'));

        if ('' === $userId || '' === $profileId || false === $from) {
            $this->addFlash('error', 'Affectation : collaborateur, profil et date de début requis.');

            return $this->redirectToRoute('pricing_admin');
        }

        try {
            $period = ($to = $this->date($request->request->get('effectiveTo'))) instanceof DateTimeImmutable
                ? EffectivePeriod::between($from, $to)
                : EffectivePeriod::since($from);

            $this->assignProfile->assign($user->tenantId(), $user, $userId, $profileId, $period);
            $this->addFlash('success', 'Collaborateur affecté au profil.');
        } catch (PricingException|InvalidArgumentException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('pricing_admin');
    }

    private function date(mixed $raw): ?DateTimeImmutable
    {
        if (is_string($raw) && '' !== $raw) {
            $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $raw);
            if (false !== $parsed) {
                return $parsed;
            }
        }

        return null;
    }
}
