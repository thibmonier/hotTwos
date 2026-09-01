<?php

declare(strict_types=1);

namespace App\UI\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Absence\DeclareAbsence;
use App\Domain\Absence\AbsenceException;
use App\UI\Api\Resource\AbsenceResource;
use DateTimeImmutable;

/**
 * Déclare une absence via le cas d'usage (US-054). Convertit les dates (`Y-m-d`) ; règles portées
 * par {@see DeclareAbsence} (type inconnu, durée nulle → 422).
 *
 * @implements ProcessorInterface<AbsenceResource, AbsenceResource>
 */
final readonly class DeclareAbsenceProcessor implements ProcessorInterface
{
    public function __construct(
        private DeclareAbsence $declareAbsence,
        private CurrentUser $currentUser,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AbsenceResource
    {
        $user = $this->currentUser->require();

        $id = $this->declareAbsence->declare(
            $user->tenantId(),
            $user,
            $data->typeId,
            $this->parseDate($data->startDate),
            $this->parseDate($data->endDate),
            $data->startsMorning,
            $data->endsAfternoon,
            $data->comment,
        );

        return new AbsenceResource(
            id: $id,
            typeId: $data->typeId,
            startDate: $data->startDate,
            endDate: $data->endDate,
            startsMorning: $data->startsMorning,
            endsAfternoon: $data->endsAfternoon,
            comment: $data->comment,
            status: 'pending',
        );
    }

    private function parseDate(?string $value): DateTimeImmutable
    {
        if (null === $value || '' === $value) {
            throw new AbsenceException('Les dates de début et de fin (Y-m-d) sont obligatoires.');
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();
        if (false === $date || (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw new AbsenceException(sprintf('Date invalide (attendu Y-m-d) : %s.', $value));
        }

        return $date;
    }
}
