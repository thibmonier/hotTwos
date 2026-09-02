<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Timesheet;

use App\Application\Period\PeriodModificationGuard;
use App\Application\Timesheet\RecordTimeEntry;
use App\Domain\Period\AccountingPeriod;
use App\Domain\Period\PeriodLockedException;
use App\Domain\Project\Project;
use App\Domain\Tenant\TenantId;
use App\Domain\Timesheet\TimesheetException;
use App\Domain\Absence\AbsenceRequest;
use App\Tests\Support\Absence\InMemoryAbsenceRequestRepository;
use App\Tests\Support\Authorization\RecordingSecurityAuditLogger;
use App\Tests\Support\Period\InMemoryAccountingPeriodRepository;
use App\Tests\Support\Period\InMemoryReopeningRequestRepository;
use App\Tests\Support\Timesheet\InMemoryProjectRepository;
use App\Tests\Support\Timesheet\InMemoryTimeEntryRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use DateTimeImmutable;

/**
 * US-050 — la saisie applique côté serveur : projet actif, plafond journalier, et upsert
 * au grain (utilisateur, projet, jour).
 */
final class RecordTimeEntryTest extends TestCase
{
    private TenantId $tenant;
    private InMemoryProjectRepository $projects;
    private InMemoryTimeEntryRepository $entries;
    private RecordTimeEntry $record;
    private InMemoryAccountingPeriodRepository $periods;
    private InMemoryAbsenceRequestRepository $absences;
    private string $projectId;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $this->projects = new InMemoryProjectRepository();
        $this->entries = new InMemoryTimeEntryRepository();
        $this->periods = new InMemoryAccountingPeriodRepository();
        $this->absences = new InMemoryAbsenceRequestRepository();
        $this->record = new RecordTimeEntry(
            $this->projects,
            $this->entries,
            new PeriodModificationGuard(
                $this->periods,
                new InMemoryReopeningRequestRepository(),
                new RecordingSecurityAuditLogger(),
                new MockClock(new DateTimeImmutable('2026-10-01 00:00:00')),
            ),
            $this->absences,
        );

        $project = new Project($this->tenant, 'PRJ-1', 'Refonte');
        $this->projects->save($project);
        $this->projectId = $project->id();
    }

    public function testRefusesRecordingOnAClosedPeriod(): void
    {
        $closed = new AccountingPeriod($this->tenant, '2026-09');
        $closed->close('admin', new DateTimeImmutable('2026-10-01 10:00:00'));
        $this->periods->save($closed);

        $this->expectException(PeriodLockedException::class);
        $this->record->record($this->tenant, 'camille', $this->projectId, new DateTimeImmutable('2026-09-15'), 240);
    }

    public function testRefusesProductionOnAValidatedAbsenceDay(): void
    {
        $absence = new AbsenceRequest($this->tenant, 'camille', 'type-1', new DateTimeImmutable('2026-09-14'), new DateTimeImmutable('2026-09-18'), true, true, new DateTimeImmutable('2026-08-01'));
        $absence->validate('marc', new DateTimeImmutable('2026-08-20'));
        $this->absences->save($absence);

        $this->expectException(TimesheetException::class);
        $this->record->record($this->tenant, 'camille', $this->projectId, new DateTimeImmutable('2026-09-15'), 240);
    }

    public function testRecordsAnEntry(): void
    {
        $this->record->record($this->tenant, 'camille', $this->projectId, new DateTimeImmutable('2026-09-15'), 240, 'matinée');

        self::assertCount(1, $this->entries->entries);
        self::assertSame(240, $this->entries->entries[0]->minutes());
    }

    public function testUpsertsSameProjectAndDay(): void
    {
        $date = new DateTimeImmutable('2026-09-15');
        $this->record->record($this->tenant, 'camille', $this->projectId, $date, 120);
        $this->record->record($this->tenant, 'camille', $this->projectId, $date, 300, 'corrigé');

        self::assertCount(1, $this->entries->entries, 'Une re-saisie ajuste la ligne, ne la duplique pas.');
        self::assertSame(300, $this->entries->entries[0]->minutes());
        self::assertSame('corrigé', $this->entries->entries[0]->comment());
    }

    public function testRejectsInactiveOrUnknownProject(): void
    {
        $this->expectException(TimesheetException::class);

        $this->record->record($this->tenant, 'camille', 'projet-inexistant', new DateTimeImmutable('2026-09-15'), 60);
    }

    public function testRefusesImputationOnProjectNotInProgress(): void
    {
        // Projet métier « En préparation » (US-030, CA-2) : imputation refusée tant qu'il n'est pas « En cours ».
        $draft = Project::createBusiness($this->tenant, 'PRJ-0001', 'Refonte', 'Acme', 'marc', 12_000_000, \App\Domain\Project\ContractType::FORFAIT, null, null);
        $this->projects->save($draft);

        try {
            $this->record->record($this->tenant, 'camille', $draft->id(), new DateTimeImmutable('2026-09-15'), 120);
            self::fail('Une imputation sur un projet en préparation doit être refusée.');
        } catch (TimesheetException) {
            // attendu
        }

        $draft->changeStatus(\App\Domain\Project\ProjectStatus::EN_COURS);
        $this->record->record($this->tenant, 'camille', $draft->id(), new DateTimeImmutable('2026-09-15'), 120);
        self::assertCount(1, $this->entries->entries);
    }

    public function testEnforcesDailyCapAcrossProjects(): void
    {
        $other = new Project($this->tenant, 'PRJ-2', 'Autre');
        $this->projects->save($other);
        $date = new DateTimeImmutable('2026-09-15');

        $this->record->record($this->tenant, 'camille', $this->projectId, $date, 1200); // 20 h

        $this->expectException(TimesheetException::class);
        $this->record->record($this->tenant, 'camille', $other->id(), $date, 300); // +5 h → 25 h > 24 h
    }
}
