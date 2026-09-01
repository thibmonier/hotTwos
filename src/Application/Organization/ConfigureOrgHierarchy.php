<?php

declare(strict_types=1);

namespace App\Application\Organization;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\SecurityAuditLogger;
use App\Domain\Organization\OrganizationException;
use App\Domain\Organization\OrgUnit;
use App\Domain\Organization\OrgUnitRepository;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use Symfony\Component\Uid\Uuid;

/**
 * Paramétrage de la hiérarchie organisationnelle (US-010, T-010-04).
 *
 * Toutes les opérations exigent la permission ADMIN (ARC-19, vérifiée côté serveur — jamais
 * déléguée à l'UI). La prévention des cycles (CA-6) et la règle « pas de suppression, seulement
 * désactivation » (CA-5 / RG-REF-1) sont appliquées ici, avec traçage sécurité (HAB-6).
 */
final readonly class ConfigureOrgHierarchy
{
    /** Garde-fou anti-boucle infinie lors du parcours d'ascendance (hiérarchie corrompue). */
    private const int MAX_DEPTH = 1000;

    public function __construct(
        private Authorizer $authorizer,
        private OrgUnitRepository $units,
        private SecurityAuditLogger $audit,
    ) {
    }

    public function createUnit(TenantId $tenant, User $actor, ?string $parentId, string $name): string
    {
        $this->authorizer->ensureCan($actor, Permission::MANAGE_ORGANIZATION);

        if (null !== $parentId) {
            $this->requireUuid($parentId);
            $parent = $this->requireUnit($tenant, $parentId);
            if (!$parent->isActive()) {
                throw new OrganizationException('Impossible de rattacher une unité sous une unité désactivée.');
            }
        }

        $unit = new OrgUnit($tenant, $parentId, $name);
        $this->units->save($unit);
        $this->audit->record('org_unit_created', $tenant->toString(), $actor->getUserIdentifier(), ['unit' => $unit->id()]);

        return $unit->id();
    }

    /**
     * Déplace une unité sous un nouveau parent (ou la promeut racine si null), après vérification
     * qu'aucun cycle n'est introduit (CA-6).
     */
    public function moveUnit(TenantId $tenant, User $actor, string $unitId, ?string $newParentId): void
    {
        $this->authorizer->ensureCan($actor, Permission::MANAGE_ORGANIZATION);

        $this->requireUuid($unitId);
        $unit = $this->requireUnit($tenant, $unitId);

        if (null !== $newParentId) {
            $this->requireUuid($newParentId);
            if ($newParentId === $unitId) {
                throw new OrganizationException('Une unité ne peut pas être son propre parent.');
            }
            $this->requireUnit($tenant, $newParentId);
            $this->guardNoCycle($tenant, $unitId, $newParentId);
        }

        $unit->attachToParent($newParentId);
        $this->units->save($unit);
        $this->audit->record('org_unit_moved', $tenant->toString(), $actor->getUserIdentifier(), ['unit' => $unitId, 'parent' => $newParentId ?? '(racine)']);
    }

    /**
     * Désactive une unité (RG-REF-1) : jamais de suppression, l'unité reste visible dans l'historique.
     */
    public function deactivateUnit(TenantId $tenant, User $actor, string $unitId): void
    {
        $this->authorizer->ensureCan($actor, Permission::MANAGE_ORGANIZATION);

        $this->requireUuid($unitId);
        $unit = $this->requireUnit($tenant, $unitId);
        $unit->deactivate();
        $this->units->save($unit);
        $this->audit->record('org_unit_deactivated', $tenant->toString(), $actor->getUserIdentifier(), ['unit' => $unitId]);
    }

    /**
     * Refuse le déplacement si le nouveau parent est l'unité elle-même ou l'un de ses descendants,
     * en remontant la chaîne d'ascendance du parent visé jusqu'à la racine.
     */
    private function guardNoCycle(TenantId $tenant, string $unitId, string $newParentId): void
    {
        $currentId = $newParentId;
        $depth = 0;

        while (null !== $currentId) {
            if ($currentId === $unitId) {
                throw new OrganizationException('Le rattachement créerait un cycle dans la hiérarchie.');
            }
            if (++$depth > self::MAX_DEPTH) {
                throw new OrganizationException('Profondeur de hiérarchie anormale : parcours interrompu.');
            }
            $currentId = $this->units->find($tenant, $currentId)?->parentId();
        }
    }

    private function requireUnit(TenantId $tenant, string $id): OrgUnit
    {
        $unit = $this->units->find($tenant, $id);
        if (!$unit instanceof OrgUnit) {
            throw new OrganizationException(sprintf('Unité organisationnelle introuvable : %s.', $id));
        }

        return $unit;
    }

    private function requireUuid(string $value): void
    {
        if (!Uuid::isValid($value)) {
            throw new OrganizationException('Identifiant d\'unité invalide.');
        }
    }
}
