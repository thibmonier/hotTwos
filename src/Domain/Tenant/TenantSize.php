<?php

declare(strict_types=1);

namespace App\Domain\Tenant;

/**
 * Tailles de tenant de référence pour le dimensionnement et les jeux de test (ARC-87).
 * Volumétries issues du CDC (05 — exigences non fonctionnelles, tableau de dimensionnement).
 * Domaine pur : aucune dépendance framework (frontière Deptrac).
 */
enum TenantSize: string
{
    case Small = 'small';
    case Medium = 'medium';
    case Large = 'large';

    /** Collaborateurs actifs. */
    public function collaborators(): int
    {
        return match ($this) {
            self::Small => 10,
            self::Medium => 50,
            self::Large => 150,
        };
    }

    /** Projets actifs simultanés. */
    public function activeProjects(): int
    {
        return match ($this) {
            self::Small => 15,
            self::Medium => 80,
            self::Large => 300,
        };
    }

    /** Lignes de temps par an (ordre de grandeur). */
    public function timeEntriesPerYear(): int
    {
        return match ($this) {
            self::Small => 12_000,
            self::Medium => 60_000,
            self::Large => 200_000,
        };
    }
}
