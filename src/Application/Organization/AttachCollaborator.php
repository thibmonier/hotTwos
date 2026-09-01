<?php

declare(strict_types=1);

namespace App\Application\Organization;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\SecurityAuditLogger;
use App\Domain\Organization\OrganizationException;
use App\Domain\Organization\OrgMembership;
use App\Domain\Organization\OrgMembershipRepository;
use App\Domain\Organization\OrgUnit;
use App\Domain\Organization\OrgUnitRepository;
use App\Domain\Shared\EffectivePeriod;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;

/**
 * Rattachement historisé d'un collaborateur à une unité (US-010, T-010-04, EF-REF-2).
 *
 * Exige la permission ADMIN (ARC-19). La cible doit être une unité active. Deux rattachements
 * d'un même collaborateur ne peuvent pas se chevaucher (période de validité), garantissant un
 * historique cohérent sans trou de responsabilité. Traçage sécurité (HAB-6).
 */
final readonly class AttachCollaborator
{
    public function __construct(
        private Authorizer $authorizer,
        private OrgUnitRepository $units,
        private OrgMembershipRepository $memberships,
        private SecurityAuditLogger $audit,
    ) {
    }

    public function attach(TenantId $tenant, User $actor, string $userId, string $unitId, EffectivePeriod $period): string
    {
        $this->authorizer->ensureCan($actor, Permission::MANAGE_ORGANIZATION);

        $unit = $this->units->find($tenant, $unitId);
        if (!$unit instanceof OrgUnit) {
            throw new OrganizationException(sprintf('Unité organisationnelle introuvable : %s.', $unitId));
        }
        if (!$unit->isActive()) {
            throw new OrganizationException('Impossible de rattacher un collaborateur à une unité désactivée.');
        }

        $this->guardNoOverlap($tenant, $userId, $period);

        $membership = new OrgMembership($tenant, $userId, $unitId, $period);
        $this->memberships->save($membership);
        $this->audit->record('collaborator_attached', $tenant->toString(), $actor->getUserIdentifier(), [
            'user' => $userId,
            'unit' => $unitId,
        ]);

        return $membership->id();
    }

    private function guardNoOverlap(TenantId $tenant, string $userId, EffectivePeriod $period): void
    {
        foreach ($this->memberships->findForUser($tenant, $userId) as $existing) {
            if ($existing->period()->overlaps($period)) {
                throw new OrganizationException('Le rattachement chevauche une période existante pour ce collaborateur.');
            }
        }
    }
}
