<?php

declare(strict_types=1);

namespace App\UI\Api\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\UI\Api\State\DefineProfileRateProcessor;
use App\UI\Api\State\ProfileRateCollectionProvider;

/**
 * DTO d'entrée tarifaire (US-011, ADR-4/ARC-18). Montants en centimes entiers (INV-2), dates au
 * format `Y-m-d`. La collection filtre par `profileId` pour restituer l'historique tarifaire.
 * `confirmRetroactive` permet de valider explicitement une saisie rétroactive (CA-3).
 */
#[ApiResource(
    shortName: 'ProfileRate',
    operations: [
        new GetCollection(uriTemplate: '/profile-rates', provider: ProfileRateCollectionProvider::class),
        new Post(uriTemplate: '/profile-rates', status: 201, processor: DefineProfileRateProcessor::class),
    ],
)]
final class ProfileRateResource
{
    public function __construct(
        public ?string $id = null,
        public string $profileId = '',
        public ?string $effectiveFrom = null,
        public ?string $effectiveTo = null,
        public int $costPriceCents = 0,
        public int $sellingPriceCents = 0,
        public bool $confirmRetroactive = false,
    ) {
    }
}
