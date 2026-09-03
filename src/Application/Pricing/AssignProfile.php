<?php

declare(strict_types=1);

namespace App\Application\Pricing;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\SecurityAuditLogger;
use App\Domain\Pricing\PricingException;
use App\Domain\Pricing\Profile;
use App\Domain\Pricing\ProfileAssignment;
use App\Domain\Pricing\ProfileAssignmentRepository;
use App\Domain\Pricing\ProfileRepository;
use App\Domain\Shared\EffectivePeriod;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use Symfony\Component\Uid\Uuid;

/**
 * US-060 (T-060-01) — affecte un collaborateur à un profil de tarification sur une période.
 *
 * Sans affectation, {@see \App\Application\Valuation\ValueValidatedTimeHandler} ne résout aucun profil
 * et la valorisation reste `MISSING_RATE` (cause du finding F2 de la recette US-069). Ce cas d'usage
 * fournit le maillon manquant. Réservé aux porteurs de MANAGE_PRICING (deny-by-default, règle 11).
 */
final readonly class AssignProfile
{
    public function __construct(
        private Authorizer $authorizer,
        private ProfileRepository $profiles,
        private ProfileAssignmentRepository $assignments,
        private SecurityAuditLogger $audit,
    ) {
    }

    public function assign(
        TenantId $tenant,
        User $actor,
        string $userId,
        string $profileId,
        EffectivePeriod $period,
    ): string {
        $this->authorizer->ensureCan($actor, Permission::MANAGE_PRICING);

        if (!Uuid::isValid($userId)) {
            throw new PricingException('Identifiant de collaborateur invalide.');
        }
        if (!Uuid::isValid($profileId)) {
            throw new PricingException('Identifiant de profil invalide.');
        }

        $profile = $this->profiles->find($tenant, $profileId);
        if (!$profile instanceof Profile) {
            throw new PricingException(sprintf('Profil introuvable : %s.', $profileId));
        }
        if (!$profile->isActive()) {
            throw new PricingException('Impossible d\'affecter un profil désactivé.');
        }

        $this->guardNoOverlap($tenant, $userId, $period);

        $assignment = new ProfileAssignment($tenant, $userId, $profileId, $period);
        $this->assignments->save($assignment);

        $this->audit->record(
            'profile_assigned',
            $tenant->toString(),
            $actor->getUserIdentifier(),
            ['user' => $userId, 'profile' => $profileId, 'effective_from' => $period->from()->format('Y-m-d')],
        );

        return $assignment->id();
    }

    private function guardNoOverlap(TenantId $tenant, string $userId, EffectivePeriod $period): void
    {
        foreach ($this->assignments->findForUser($tenant, $userId) as $existing) {
            if ($existing->period()->overlaps($period)) {
                throw new PricingException('La période chevauche une affectation existante pour ce collaborateur.');
            }
        }
    }
}
