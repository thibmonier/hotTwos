<?php

declare(strict_types=1);

namespace App\UI\Api\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\UI\Api\State\AbsenceImpactProvider;

/**
 * US-091b (CA-2/CA-3) — impact d'une demande d'absence sur une période sélectionnée : nombre de jours
 * **ouvrés** concernés (hors week-ends, fériés et fermetures), solde projeté après cette demande, et
 * signalement d'un éventuel conflit (fermeture d'entreprise ou absence déjà posée).
 */
#[ApiResource(
    shortName: 'AbsenceImpact',
    operations: [
        new Get(uriTemplate: '/absences/impact', provider: AbsenceImpactProvider::class),
    ],
)]
final class AbsenceImpactResource
{
    public function __construct(
        public int $businessDays = 0,
        public float $projectedBalance = 0.0,
        public bool $hasConflict = false,
        public ?string $conflictMessage = null,
    ) {
    }
}
