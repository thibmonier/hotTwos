<?php

declare(strict_types=1);

namespace App\UI\Api\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\UI\Api\State\AbsenceCollectionProvider;
use App\UI\Api\State\DeclareAbsenceProcessor;
use App\UI\Api\State\DecideAbsenceProcessor;

/**
 * DTO de demande d'absence (US-054). Dates au format `Y-m-d`, maille demi-journée via
 * `startsMorning` / `endsAfternoon`. **Aucun champ de donnée de santé** (HAB-3).
 */
#[ApiResource(
    shortName: 'Absence',
    operations: [
        new GetCollection(uriTemplate: '/absences', provider: AbsenceCollectionProvider::class),
        new Post(uriTemplate: '/absences', status: 201, processor: DeclareAbsenceProcessor::class),
        new Post(uriTemplate: '/absences/{id}/decision', status: 200, read: false, processor: DecideAbsenceProcessor::class),
    ],
)]
final class AbsenceResource
{
    public function __construct(
        public ?string $id = null,
        public string $typeId = '',
        public ?string $startDate = null,
        public ?string $endDate = null,
        public bool $startsMorning = true,
        public bool $endsAfternoon = true,
        public ?string $comment = null,
        public ?string $status = null,
        // Décision (opération /decision) : approbation + motif de refus éventuel.
        public bool $approved = false,
        public ?string $reason = null,
    ) {
    }
}
