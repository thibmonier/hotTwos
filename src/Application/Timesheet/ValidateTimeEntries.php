<?php

declare(strict_types=1);

namespace App\Application\Timesheet;

use App\Application\Authorization\Authorizer;
use App\Domain\Authorization\AccessDeniedException;
use App\Domain\Authorization\Permission;
use App\Domain\Authorization\SecurityAuditLogger;
use App\Domain\Project\Project;
use App\Domain\Project\ProjectRepository;
use App\Domain\Tenant\TenantId;
use App\Application\Timesheet\Message\TimeEntriesValidated;
use App\Domain\Timesheet\TimeEntryRepository;
use App\Domain\Timesheet\TimesheetException;
use App\Domain\User\User;
use DateTimeImmutable;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Validation/refus des imputations par lot (US-055, ARC-19/ARC-106).
 *
 * Double contrôle côté serveur : la **permission** fonctionnelle (VALIDATE_TIME, via le
 * RBAC US-003) et le **périmètre** « ses projets » (l'acteur doit être responsable du
 * projet de chaque imputation). Tout est atomique au sens du lot : si une seule imputation
 * est hors périmètre, rien n'est décidé (403). Un refus exige un motif. Chaque décision est
 * tracée (HAB-6).
 */
final readonly class ValidateTimeEntries
{
    public function __construct(
        private Authorizer $authorizer,
        private ProjectRepository $projects,
        private TimeEntryRepository $entries,
        private SecurityAuditLogger $audit,
        private MessageBusInterface $bus,
    ) {
    }

    /**
     * @param list<string> $entryIds
     */
    public function validate(TenantId $tenant, User $actor, array $entryIds): int
    {
        return $this->decide($tenant, $actor, $entryIds, null);
    }

    /**
     * @param list<string> $entryIds
     */
    public function reject(TenantId $tenant, User $actor, array $entryIds, string $reason): int
    {
        if ('' === trim($reason)) {
            throw new TimesheetException('Un motif est obligatoire pour refuser des imputations.');
        }

        return $this->decide($tenant, $actor, $entryIds, $reason);
    }

    /**
     * @param list<string> $entryIds
     */
    private function decide(TenantId $tenant, User $actor, array $entryIds, ?string $reason): int
    {
        $this->authorizer->ensureCan($actor, Permission::VALIDATE_TIME);

        $entries = $this->entries->findByIds($tenant, $entryIds);

        // Périmètre « ses projets » : chaque imputation doit relever d'un projet dont l'acteur
        // est responsable — vérifié pour tout le lot avant d'appliquer quoi que ce soit.
        foreach ($entries as $entry) {
            $project = $this->projects->find($tenant, $entry->projectId());
            if (!$project instanceof Project || !$project->isResponsible($actor->id())) {
                $this->audit->record('out_of_scope_validation_attempt', $tenant->toString(), $actor->getUserIdentifier(), [
                    'time_entry' => $entry->id(),
                    'project' => $entry->projectId(),
                ]);

                throw new AccessDeniedException('Validation hors périmètre : ce projet n\'est pas placé sous votre responsabilité.');
            }
        }

        $decidedAt = new DateTimeImmutable();
        $validatedIds = [];
        foreach ($entries as $entry) {
            if (null === $reason) {
                $entry->validate($actor->id(), $decidedAt);
                $validatedIds[] = $entry->id();
            } else {
                $entry->reject($actor->id(), $reason, $decidedAt);
            }
            $this->entries->save($entry);
        }

        $this->audit->record(
            null === $reason ? 'time_entries_validated' : 'time_entries_rejected',
            $tenant->toString(),
            $actor->getUserIdentifier(),
            ['count' => (string) count($entries)],
        );

        // Couplage par événement (US-060) : le temps validé déclenche sa valorisation asynchrone.
        if ([] !== $validatedIds) {
            $this->bus->dispatch(new TimeEntriesValidated($tenant->toString(), $validatedIds, $decidedAt));
        }

        return count($entries);
    }
}
