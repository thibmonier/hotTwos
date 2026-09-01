<?php

declare(strict_types=1);

namespace App\UI\Api\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\UI\Api\State\CreateProfileProcessor;
use App\UI\Api\State\DeactivateProfileProcessor;
use App\UI\Api\State\ProfileCollectionProvider;
use App\UI\Api\State\ProfileItemProvider;

/**
 * DTO de profil de tarification (US-011, ADR-4/ARC-18). `calculationMode` : direct/loaded/full.
 * `DELETE` désactive (RG-REF-1), jamais de suppression dure.
 */
#[ApiResource(
    shortName: 'Profile',
    operations: [
        new GetCollection(uriTemplate: '/profiles', provider: ProfileCollectionProvider::class),
        new Post(uriTemplate: '/profiles', status: 201, processor: CreateProfileProcessor::class),
        new Delete(uriTemplate: '/profiles/{id}', status: 204, provider: ProfileItemProvider::class, processor: DeactivateProfileProcessor::class),
    ],
)]
final class ProfileResource
{
    public function __construct(
        public ?string $id = null,
        public string $name = '',
        public string $calculationMode = 'direct',
        public bool $active = true,
    ) {
    }
}
