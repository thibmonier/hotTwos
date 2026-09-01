<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Pricing\Profile;
use App\Domain\Pricing\ProfileRepository;
use App\Domain\User\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * US-011 (T-011-06) — écran d'administration des profils et de leurs tarifs (adaptateur web).
 *
 * Réservé à l'admin (habilitation applicative, ARC-106). Les actions (création, désactivation,
 * définition d'un tarif) et l'historique tarifaire passent par l'API via un contrôleur Stimulus.
 */
final class ProfilePageController extends AbstractController
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly ProfileRepository $profiles,
    ) {
    }

    #[Route('/profils', name: 'pricing_admin', methods: ['GET'])]
    public function index(#[CurrentUser] User $user): Response
    {
        $this->authorizer->ensureCan($user, Permission::MANAGE_PRICING);

        $rows = array_map(
            static fn (Profile $profile): array => [
                'id' => $profile->id(),
                'name' => $profile->name(),
                'mode' => $profile->calculationMode()->value,
                'active' => $profile->isActive(),
            ],
            $this->profiles->findByTenant($user->tenantId()),
        );

        return $this->render('pricing/index.html.twig', ['rows' => $rows]);
    }
}
