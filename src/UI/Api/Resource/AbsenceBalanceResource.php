<?php

declare(strict_types=1);

namespace App\UI\Api\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\UI\Api\State\AbsenceBalanceProvider;

/**
 * Compteurs d'absences du collaborateur authentifié (US-054, EF-TMP-16).
 */
#[ApiResource(
    shortName: 'AbsenceBalance',
    operations: [
        new Get(uriTemplate: '/absences/balance', provider: AbsenceBalanceProvider::class),
    ],
)]
final class AbsenceBalanceResource
{
    public function __construct(
        public float $acquired = 0.0,
        public float $taken = 0.0,
        public float $pending = 0.0,
        public float $balance = 0.0,
        public float $projectedBalance = 0.0,
    ) {
    }
}
