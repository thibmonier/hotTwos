<?php

declare(strict_types=1);

namespace App\UI\Api\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\UI\Api\State\CreateOrgUnitProcessor;
use App\UI\Api\State\DeactivateOrgUnitProcessor;
use App\UI\Api\State\OrgUnitCollectionProvider;
use App\UI\Api\State\OrgUnitItemProvider;

/**
 * DTO d'unité organisationnelle (US-010, ADR-4/ARC-18 — jamais l'entité de persistance).
 *
 * `DELETE` ne supprime pas : il **désactive** l'unité (RG-REF-1), l'API n'expose aucune
 * suppression dure. L'habilitation ADMIN est vérifiée dans les cas d'usage / providers.
 */
#[ApiResource(
    shortName: 'OrgUnit',
    operations: [
        new GetCollection(uriTemplate: '/org-units', provider: OrgUnitCollectionProvider::class),
        new Post(uriTemplate: '/org-units', status: 201, processor: CreateOrgUnitProcessor::class),
        new Delete(uriTemplate: '/org-units/{id}', status: 204, provider: OrgUnitItemProvider::class, processor: DeactivateOrgUnitProcessor::class),
    ],
)]
final class OrgUnitResource
{
    public function __construct(
        public ?string $id = null,
        public ?string $parentId = null,
        public string $name = '',
        public bool $active = true,
    ) {
    }
}
