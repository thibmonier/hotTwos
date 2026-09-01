<?php

declare(strict_types=1);

namespace App\UI\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\Absence\AbsenceBalance;
use App\UI\Api\Resource\AbsenceBalanceResource;

/**
 * Fournit les compteurs d'absences du collaborateur authentifié (US-054, EF-TMP-16).
 *
 * @implements ProviderInterface<AbsenceBalanceResource>
 */
final readonly class AbsenceBalanceProvider implements ProviderInterface
{
    public function __construct(
        private AbsenceBalance $balance,
        private CurrentUser $currentUser,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AbsenceBalanceResource
    {
        $user = $this->currentUser->require();
        $counters = $this->balance->for($user->tenantId(), $user->id());

        return new AbsenceBalanceResource(
            acquired: $counters->acquired,
            taken: $counters->taken,
            pending: $counters->pending,
            balance: $counters->balance(),
            projectedBalance: $counters->projectedBalance(),
        );
    }
}
