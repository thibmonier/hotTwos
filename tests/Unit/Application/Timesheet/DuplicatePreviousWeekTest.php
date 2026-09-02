<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Timesheet;

use App\Application\Period\PeriodModificationGuard;
use App\Application\Timesheet\DuplicatePreviousWeek;
use App\Application\Timesheet\RecordTimeEntry;
use App\Application\Timesheet\RecordWeek;
use App\Domain\Project\Project;
use App\Domain\Tenant\TenantId;
use App\Domain\Timesheet\TimeEntry;
use App\Tests\Support\Absence\InMemoryAbsenceRequestRepository;
use App\Tests\Support\Authorization\RecordingSecurityAuditLogger;
use App\Tests\Support\Period\InMemoryAccountingPeriodRepository;
use App\Tests\Support\Period\InMemoryReopeningRequestRepository;
use App\Tests\Support\Timesheet\InMemoryProjectRepository;
use App\Tests\Support\Timesheet\InMemoryTimeEntryRepository;
use Symfony\Component\Clock\MockClock;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;

/**
 * US-051 — la duplication reporte les imputations de la semaine N-1 au même jour de la
 * semaine cible.
 */
final class DuplicatePreviousWeekTest extends TestCase
{
    public function testCopiesPreviousWeekEntriesToTargetWeek(): void
    {
        $tenant = TenantId::generate();
        $projects = new InMemoryProjectRepository();
        $entries = new InMemoryTimeEntryRepository();
        $duplicate = new DuplicatePreviousWeek($entries, new RecordWeek(new RecordTimeEntry(
            $projects,
            $entries,
            new PeriodModificationGuard(
                new InMemoryAccountingPeriodRepository(),
                new InMemoryReopeningRequestRepository(),
                new RecordingSecurityAuditLogger(),
                new MockClock(),
            ),
            new InMemoryAbsenceRequestRepository(),
            new \App\Tests\Support\Project\InMemoryProjectAssignmentRepository(),
            new \App\Tests\Support\Project\InMemoryExceptionalImputationOpeningRepository(),
        )));

        $project = new Project($tenant, 'PRJ-1', 'Refonte');
        $projects->save($project);

        // Semaine précédente : lundi 2026-09-07 et mardi 2026-09-08.
        $entries->save(new TimeEntry($tenant, 'camille', $project->id(), new DateTimeImmutable('2026-09-07'), 420));
        $entries->save(new TimeEntry($tenant, 'camille', $project->id(), new DateTimeImmutable('2026-09-08'), 300));

        // Cible : semaine du lundi 2026-09-14.
        $errors = $duplicate->into($tenant, 'camille', new DateTimeImmutable('2026-09-14'));

        self::assertSame([], $errors);
        $targetDays = array_map(
            static fn (TimeEntry $entry): string => $entry->workDate()->format('Y-m-d'),
            $entries->findForUserInRange($tenant, 'camille', new DateTimeImmutable('2026-09-14'), new DateTimeImmutable('2026-09-20')),
        );
        sort($targetDays);
        self::assertSame(['2026-09-14', '2026-09-15'], $targetDays, 'Les imputations sont reportées au même jour +7.');
    }
}
