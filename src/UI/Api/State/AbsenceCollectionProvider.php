<?php

declare(strict_types=1);

namespace App\UI\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Domain\Absence\AbsenceRequest;
use App\Domain\Absence\AbsenceRequestRepository;
use App\UI\Api\Resource\AbsenceResource;

/**
 * Liste les demandes d'absence du collaborateur authentifié (US-054) — périmètre « soi-même »
 * automatique (RBAC : un collaborateur ne voit que ses propres absences).
 *
 * @implements ProviderInterface<AbsenceResource>
 */
final readonly class AbsenceCollectionProvider implements ProviderInterface
{
    public function __construct(
        private AbsenceRequestRepository $requests,
        private CurrentUser $currentUser,
    ) {
    }

    /**
     * @return list<AbsenceResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->currentUser->require();

        return array_map(
            static fn (AbsenceRequest $r): AbsenceResource => new AbsenceResource(
                id: $r->id(),
                typeId: $r->typeId(),
                startDate: $r->startDate()->format('Y-m-d'),
                endDate: $r->endDate()->format('Y-m-d'),
                comment: $r->comment(),
                status: $r->status()->value,
            ),
            $this->requests->findForUser($user->tenantId(), $user->id()),
        );
    }
}
