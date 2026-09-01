<?php

declare(strict_types=1);

namespace App\Domain\Reminder;

use App\Domain\Tenant\TenantId;

/**
 * Port de persistance des préférences de relance (US-056, DIP). Tenant explicite.
 */
interface ReminderPreferenceRepository
{
    public function save(ReminderPreference $preference): void;

    public function findForUser(TenantId $tenant, string $userId): ?ReminderPreference;

    /**
     * Identifiants des collaborateurs ayant explicitement désactivé leurs relances (opt-out) —
     * exclus du calcul du moteur (CA-2).
     *
     * @return list<string>
     */
    public function findOptedOutUserIds(TenantId $tenant): array;
}
