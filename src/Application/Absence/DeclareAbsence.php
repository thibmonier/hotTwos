<?php

declare(strict_types=1);

namespace App\Application\Absence;

use App\Application\Absence\Message\AbsenceDeclared;
use App\Domain\Absence\AbsenceException;
use App\Domain\Absence\AbsenceRequest;
use App\Domain\Absence\AbsenceRequestRepository;
use App\Domain\Absence\AbsenceType;
use App\Domain\Absence\AbsenceTypeRepository;
use App\Domain\Authorization\SecurityAuditLogger;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use InvalidArgumentException;

/**
 * Déclaration d'une absence par un collaborateur **pour lui-même** (US-054, CA-1).
 *
 * Self-service (l'acteur est le collaborateur — pas de permission dédiée) : valide le type et les
 * dates, crée une demande `pending`, notifie le manager (message async) et trace l'opération.
 */
final readonly class DeclareAbsence
{
    public function __construct(
        private AbsenceTypeRepository $types,
        private AbsenceRequestRepository $requests,
        private SecurityAuditLogger $audit,
        private MessageBusInterface $bus,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @return string identifiant de la demande créée
     */
    public function declare(
        TenantId $tenant,
        User $actor,
        string $typeId,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        bool $startsMorning = true,
        bool $endsAfternoon = true,
        ?string $comment = null,
    ): string {
        if (!$this->types->findById($tenant, $typeId) instanceof AbsenceType) {
            throw new AbsenceException('Type d\'absence inconnu.');
        }

        try {
            $request = new AbsenceRequest(
                $tenant,
                $actor->id(),
                $typeId,
                $startDate,
                $endDate,
                $startsMorning,
                $endsAfternoon,
                $this->clock->now(),
                $comment,
            );
        } catch (InvalidArgumentException $exception) {
            throw new AbsenceException($exception->getMessage(), $exception->getCode(), $exception);
        }

        $this->requests->save($request);

        $this->audit->record('absence_declaree', $tenant->toString(), $actor->getUserIdentifier(), [
            'request' => $request->id(),
            'type' => $typeId,
        ]);
        $this->bus->dispatch(new AbsenceDeclared($tenant->toString(), $request->id()));

        return $request->id();
    }
}
