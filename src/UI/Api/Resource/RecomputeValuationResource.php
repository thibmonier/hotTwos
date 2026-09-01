<?php

declare(strict_types=1);

namespace App\UI\Api\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\UI\Api\State\RecomputeValuationProcessor;

/**
 * Déclenchement manuel du recalcul de valorisation d'une période (US-060, CA-5).
 *
 * `POST /api/valorisation/recompute?period=YYYY-MM`. Habilitation `RECOMPUTE_VALUATION` (403),
 * période clôturée → 423 Locked, période invalide → 422 (portés par le cas d'usage + listeners).
 */
#[ApiResource(
    shortName: 'ValuationRecompute',
    operations: [
        new Post(uriTemplate: '/valorisation/recompute', status: 200, read: false, processor: RecomputeValuationProcessor::class),
    ],
)]
final class RecomputeValuationResource
{
    public function __construct(
        public ?string $period = null,
        public int $recomputed = 0,
    ) {
    }
}
