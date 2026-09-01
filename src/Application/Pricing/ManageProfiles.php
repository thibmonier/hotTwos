<?php

declare(strict_types=1);

namespace App\Application\Pricing;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\SecurityAuditLogger;
use App\Domain\Pricing\CalculationMode;
use App\Domain\Pricing\PricingException;
use App\Domain\Pricing\Profile;
use App\Domain\Pricing\ProfileRepository;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use Symfony\Component\Uid\Uuid;

/**
 * Gestion des profils de tarification (US-011) : création et désactivation, réservées à l'admin
 * (MANAGE_PRICING, ARC-19). Jamais de suppression — désactivation (RG-REF-1), traçage (HAB-6).
 */
final readonly class ManageProfiles
{
    public function __construct(
        private Authorizer $authorizer,
        private ProfileRepository $profiles,
        private SecurityAuditLogger $audit,
    ) {
    }

    public function create(TenantId $tenant, User $actor, string $name, CalculationMode $mode): string
    {
        $this->authorizer->ensureCan($actor, Permission::MANAGE_PRICING);

        $profile = new Profile($tenant, $name, $mode);
        $this->profiles->save($profile);
        $this->audit->record('profile_created', $tenant->toString(), $actor->getUserIdentifier(), ['profile' => $profile->id()]);

        return $profile->id();
    }

    public function deactivate(TenantId $tenant, User $actor, string $profileId): void
    {
        $this->authorizer->ensureCan($actor, Permission::MANAGE_PRICING);

        if (!Uuid::isValid($profileId)) {
            throw new PricingException('Identifiant de profil invalide.');
        }

        $profile = $this->profiles->find($tenant, $profileId);
        if (!$profile instanceof Profile) {
            throw new PricingException(sprintf('Profil introuvable : %s.', $profileId));
        }

        $profile->deactivate();
        $this->profiles->save($profile);
        $this->audit->record('profile_deactivated', $tenant->toString(), $actor->getUserIdentifier(), ['profile' => $profileId]);
    }
}
