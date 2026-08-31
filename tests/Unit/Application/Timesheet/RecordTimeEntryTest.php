<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Timesheet;

use App\Application\Timesheet\RecordTimeEntry;
use App\Domain\Project\Project;
use App\Domain\Tenant\TenantId;
use App\Domain\Timesheet\TimesheetException;
use App\Tests\Support\Timesheet\InMemoryProjectRepository;
use App\Tests\Support\Timesheet\InMemoryTimeEntryRepository;
use PHPUnit\Framework\TestCase;
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
    private string $projectId;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $this->projects = new InMemoryProjectRepository();
        $this->entries = new InMemoryTimeEntryRepository();
        $this->record = new RecordTimeEntry($this->projects, $this->entries);

        $project = new Project($this->tenant, 'PRJ-1', 'Refonte');
        $this->projects->save($project);
        $this->projectId = $project->id();
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
