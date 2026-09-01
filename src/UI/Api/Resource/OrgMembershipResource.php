<?php

declare(strict_types=1);

namespace App\UI\Api\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\UI\Api\State\AttachCollaboratorProcessor;
use App\UI\Api\State\OrgMembershipCollectionProvider;

/**
 * DTO de rattachement historisé (US-010, ADR-4/ARC-18). Dates au format `Y-m-d` (date d'effet).
 * La collection filtre par `userId` pour restituer la timeline d'un collaborateur.
 */
#[ApiResource(
    shortName: 'OrgMembership',
    operations: [
        new GetCollection(uriTemplate: '/org-memberships', provider: OrgMembershipCollectionProvider::class),
        new Post(uriTemplate: '/org-memberships', status: 201, processor: AttachCollaboratorProcessor::class),
    ],
)]
final class OrgMembershipResource
{
    public function __construct(
        public ?string $id = null,
        public string $userId = '',
        public string $orgUnitId = '',
        public ?string $effectiveFrom = null,
        public ?string $effectiveTo = null,
    ) {
    }
}
