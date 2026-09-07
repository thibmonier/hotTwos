<?php

declare(strict_types=1);

namespace App\Domain\Audit;

use App\Domain\Tenant\TenantId;

/**
 * US-020 (EF-REF-33, INV-7) — journal d'audit du paramétrage **append-only** : uniquement enregistrement
 * et lecture, jamais de modification ni suppression d'entrée.
 */
interface ConfigAuditRecorder
{
    public function record(
        TenantId $tenant,
        string $actorUserId,
        AuditAction $action,
        string $objectType,
        string $objectLabel,
        ?string $field = null,
        ?string $valueBefore = null,
        ?string $valueAfter = null,
    ): void;

    /**
     * Journal du tenant, filtré, trié par date décroissante.
     *
     * @return list<ConfigAuditEntry>
     */
    public function findForTenant(TenantId $tenant, ?string $objectType = null, ?string $actorUserId = null): array;
}
