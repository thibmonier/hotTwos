<?php

declare(strict_types=1);

namespace App\Domain\Reminder;

use App\Domain\Tenant\TenantId;
use DateTimeImmutable;

/**
 * Port de persistance du journal de relances (US-056, DIP). Tenant explicite.
 */
interface ReminderLogRepository
{
    public function save(ReminderLog $log): void;

    /**
     * Dernière relance émise pour `(collaborateur, semaine)` — base du plancher anti-spam et du
     * rang d'escalade. `null` si aucune relance n'a encore été émise pour cette semaine.
     */
    public function latestFor(TenantId $tenant, string $userId, DateTimeImmutable $weekStart): ?ReminderLog;

    /**
     * Historique des relances émises, plus récentes d'abord ; filtrable par collaborateur.
     *
     * @return list<ReminderLog>
     */
    public function findRecent(TenantId $tenant, ?string $userId, int $limit): array;
}
