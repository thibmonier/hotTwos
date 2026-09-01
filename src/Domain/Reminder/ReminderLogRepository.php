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
     * Dernière relance émise pour `(collaborateur, semaine)` — base du rang d'escalade et de la
     * borne de fréquence par semaine. `null` si aucune relance n'a encore été émise pour cette semaine.
     */
    public function latestFor(TenantId $tenant, string $userId, DateTimeImmutable $weekStart): ?ReminderLog;

    /**
     * Une relance a-t-elle déjà été émise à ce collaborateur ce jour-là, **toutes semaines
     * confondues** ? Pivot du plancher anti-spam par jour ouvré (CA-4) : au plus une relance par
     * collaborateur et par jour, même s'il accumule plusieurs semaines en retard.
     */
    public function sentOnDay(TenantId $tenant, string $userId, DateTimeImmutable $day): bool;

    /**
     * Historique des relances émises, plus récentes d'abord ; filtrable par collaborateur.
     *
     * @return list<ReminderLog>
     */
    public function findRecent(TenantId $tenant, ?string $userId, int $limit): array;
}
