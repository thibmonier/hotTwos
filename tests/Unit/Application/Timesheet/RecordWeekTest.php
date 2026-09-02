<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Timesheet;

use App\Application\Period\PeriodModificationGuard;
use App\Application\Timesheet\RecordTimeEntry;
use App\Application\Timesheet\RecordWeek;
use App\Application\Timesheet\WeekCell;
use App\Domain\Project\Project;
use App\Domain\Tenant\TenantId;
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
 * US-051 — l'enregistrement d'une semaine applique toutes les cellules valides en une
 * opération et remonte les cellules refusées sans interrompre le lot.
 */
final class RecordWeekTest extends TestCase
{
    private TenantId $tenant;
    private InMemoryTimeEntryRepository $entries;
    private RecordWeek $recordWeek;
    private string $projectId;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $projects = new InMemoryProjectRepository();
        $this->entries = new InMemoryTimeEntryRepository();
        $this->recordWeek = new RecordWeek(new RecordTimeEntry(
            $projects,
            $this->entries,
            new PeriodModificationGuard(
                new InMemoryAccountingPeriodRepository(),
                new InMemoryReopeningRequestRepository(),
                new RecordingSecurityAuditLogger(),
                new MockClock(),
            ),
            new InMemoryAbsenceRequestRepository(),
            new \App\Tests\Support\Project\InMemoryProjectAssignmentRepository(),
            new \App\Tests\Support\Project\InMemoryExceptionalImputationOpeningRepository(),
            new \App\Tests\Support\Project\InMemoryProjectReopeningRepository(),
        ));

        $project = new Project($this->tenant, 'PRJ-1', 'Refonte');
        $projects->save($project);
        $this->projectId = $project->id();
    }

    public function testRecordsAFullWeekInOneCall(): void
    {
        $cells = [];
        foreach (['2026-09-14', '2026-09-15', '2026-09-16', '2026-09-17', '2026-09-18'] as $day) {
            $cells[] = new WeekCell($this->projectId, new DateTimeImmutable($day), 420);
        }

        $errors = $this->recordWeek->record($this->tenant, 'camille', $cells);

        self::assertSame([], $errors);
        self::assertCount(5, $this->entries->entries);
    }

    public function testCollectsErrorsWithoutStoppingTheBatch(): void
    {
        $cells = [
            new WeekCell($this->projectId, new DateTimeImmutable('2026-09-15'), 240),
            new WeekCell('projet-inconnu', new DateTimeImmutable('2026-09-15'), 120),
            new WeekCell($this->projectId, new DateTimeImmutable('2026-09-16'), 300),
        ];

        $errors = $this->recordWeek->record($this->tenant, 'camille', $cells);

        self::assertCount(1, $errors, 'Seule la cellule au projet inconnu échoue.');
        self::assertSame('projet-inconnu', $errors[0]->projectId);
        self::assertCount(2, $this->entries->entries, 'Les 2 cellules valides sont enregistrées.');
    }
}
