<?php

declare(strict_types=1);

namespace App\UI\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Absence\DecideAbsence;
use App\UI\Api\Resource\AbsenceResource;

/**
 * Décide (valide/refuse) une demande d'absence via le cas d'usage (US-054). Habilitation
 * `VALIDATE_ABSENCE` (403) et règles portées par {@see DecideAbsence} (déjà traitée, motif → 422).
 *
 * @implements ProcessorInterface<AbsenceResource, AbsenceResource>
 */
final readonly class DecideAbsenceProcessor implements ProcessorInterface
{
    public function __construct(
        private DecideAbsence $decideAbsence,
        private CurrentUser $currentUser,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AbsenceResource
    {
        $user = $this->currentUser->require();
        $requestId = is_string($uriVariables['id'] ?? null) ? $uriVariables['id'] : '';

        if ($data->approved) {
            $this->decideAbsence->approve($user->tenantId(), $user, $requestId);
        } else {
            $this->decideAbsence->reject($user->tenantId(), $user, $requestId, (string) $data->reason);
        }

        return new AbsenceResource(id: $requestId, status: $data->approved ? 'validated' : 'rejected');
    }
}
