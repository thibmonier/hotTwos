<?php

declare(strict_types=1);

namespace App\Application\Absence;

use App\Application\Absence\Message\AbsenceDecided;
use App\Application\Authorization\Authorizer;
use App\Domain\Absence\AbsenceException;
use App\Domain\Absence\AbsenceRequest;
use App\Domain\Absence\AbsenceRequestRepository;
use App\Domain\Absence\AbsenceStatus;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\SecurityAuditLogger;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use InvalidArgumentException;

/**
 * Décision d'un manager sur une demande d'absence (US-054, CA-1/CA-5).
 *
 * Habilitation `VALIDATE_ABSENCE` (403). Valide ou refuse (motif obligatoire) une demande en
 * attente, notifie le demandeur (async) et trace la décision. Un refus laisse les compteurs
 * inchangés et rouvre la période à la saisie de production (CA-5).
 */
final readonly class DecideAbsence
{
    public function __construct(
        private Authorizer $authorizer,
        private AbsenceRequestRepository $requests,
        private SecurityAuditLogger $audit,
        private MessageBusInterface $bus,
        private ClockInterface $clock,
    ) {
    }

    public function approve(TenantId $tenant, User $manager, string $requestId): void
    {
        $request = $this->pending($tenant, $manager, $requestId);
        $request->validate($manager->id(), $this->clock->now());
        $this->requests->save($request);

        $this->audit->record('absence_validee', $tenant->toString(), $manager->getUserIdentifier(), ['request' => $requestId]);
        $this->bus->dispatch(new AbsenceDecided($tenant->toString(), $requestId, true));
    }

    public function reject(TenantId $tenant, User $manager, string $requestId, string $reason): void
    {
        $request = $this->pending($tenant, $manager, $requestId);

        try {
            $request->reject($manager->id(), $reason, $this->clock->now());
        } catch (InvalidArgumentException $exception) {
            throw new AbsenceException($exception->getMessage(), $exception->getCode(), $exception);
        }
        $this->requests->save($request);

        $this->audit->record('absence_refusee', $tenant->toString(), $manager->getUserIdentifier(), ['request' => $requestId]);
        $this->bus->dispatch(new AbsenceDecided($tenant->toString(), $requestId, false));
    }

    private function pending(TenantId $tenant, User $manager, string $requestId): AbsenceRequest
    {
        $this->authorizer->ensureCan($manager, Permission::VALIDATE_ABSENCE);

        $request = $this->requests->findById($tenant, $requestId);
        if (!$request instanceof AbsenceRequest) {
            throw new AbsenceException('Demande d\'absence introuvable.');
        }
        if (AbsenceStatus::PENDING !== $request->status()) {
            throw new AbsenceException('Cette demande d\'absence a déjà été traitée.');
        }
        // Séparation des tâches : un collaborateur ne peut pas décider de sa propre absence.
        if ($request->userId() === $manager->id()) {
            $this->audit->record('absence_auto_decision_refusee', $tenant->toString(), $manager->getUserIdentifier(), ['request' => $requestId]);

            throw new AbsenceException('Vous ne pouvez pas décider de votre propre demande d\'absence.');
        }

        return $request;
    }
}
