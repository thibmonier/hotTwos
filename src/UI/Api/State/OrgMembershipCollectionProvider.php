<?php

declare(strict_types=1);

namespace App\UI\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\Permission;
use App\Domain\Organization\OrgMembership;
use App\Domain\Organization\OrgMembershipRepository;
use App\UI\Api\Resource\OrgMembershipResource;

/**
 * Timeline des rattachements d'un collaborateur (US-010) : `GET /org-memberships?userId=…`.
 * Réservé à l'admin (ARC-19).
 *
 * @implements ProviderInterface<OrgMembershipResource>
 */
final readonly class OrgMembershipCollectionProvider implements ProviderInterface
{
    public function __construct(
        private Authorizer $authorizer,
        private CurrentUser $currentUser,
        private OrgMembershipRepository $memberships,
    ) {
    }

    /**
     * @return list<OrgMembershipResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->currentUser->require();
        $this->authorizer->ensureCan($user, Permission::MANAGE_ORGANIZATION);

        $filters = $context['filters'] ?? [];
        $userId = is_array($filters) && is_string($filters['userId'] ?? null) ? $filters['userId'] : '';
        if ('' === $userId) {
            return [];
        }

        return array_map(
            static fn (OrgMembership $m): OrgMembershipResource => new OrgMembershipResource(
                id: $m->id(),
                userId: $m->userId(),
                orgUnitId: $m->orgUnitId(),
                effectiveFrom: $m->period()->from()->format('Y-m-d'),
                effectiveTo: $m->period()->to()?->format('Y-m-d'),
            ),
            $this->memberships->findForUser($user->tenantId(), $userId),
        );
    }
}
