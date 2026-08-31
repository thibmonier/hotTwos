<?php

declare(strict_types=1);

namespace App\Domain\Analytics;

/**
 * Port du durcissement du schéma analytique (US-005, CA-4/CA-6) : active l'isolation RLS
 * et la protection anti-écriture directe. L'implémentation (DDL PostgreSQL) vit en
 * infrastructure (DIP).
 */
interface AnalyticsSchemaGuard
{
    public function harden(): void;
}
