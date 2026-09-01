<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Activity;

use App\Application\Activity\ActivitySummary;
use App\Application\Timesheet\EnsureAbsenceProject;
use App\Domain\Activity\ActivityType;
use App\Domain\Project\Project;
use App\Domain\Tenant\TenantId;
use App\Domain\Timesheet\TimeEntry;
use App\Tests\Support\Timesheet\InMemoryProjectRepository;
use App\Tests\Support\Timesheet\InMemoryTimeEntryRepository;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;
use DateTimeZone;

/**
 * US-059 (T-059-01/05) — la synthèse répartit le temps par projet et par type (production/absence),
 * ne compte que les temps validés et soumis (RG-TMP-4, refusés exclus), et calcule le taux
 * d'occupation. « Maintenant » = mercredi 16/09/2026.
 */
final class ActivitySummaryTest extends TestCase
{
    private const string USER = '018f9c4e-0000-7000-8000-0000000000aa';

    private TenantId $tenant;
    private InMemoryTimeEntryRepository $entries;
    private InMemoryProjectRepository $projects;
    private string $alpha;
    private string $beta;
    private string $absence;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $this->entries = new InMemoryTimeEntryRepository();
        $this->projects = new InMemoryProjectRepository();

        $alpha = new Project($this->tenant, 'ALPHA', 'Projet Alpha');
        $beta = new Project($this->tenant, 'BETA', 'Projet Beta');
        $absence = new Project($this->tenant, EnsureAbsenceProject::CODE, 'Absence');
        $this->projects->save($alpha);
        $this->projects->save($beta);
        $this->projects->save($absence);
        $this->alpha = $alpha->id();
        $this->beta = $beta->id();
        $this->absence = $absence->id();
    }

    public function testAggregatesByProjectAndTypeExcludingRejected(): void
    {
        $this->entries->save($this->validated($this->alpha, '2026-09-14', 420));
        $this->entries->save($this->submitted($this->beta, '2026-09-15', 180));
        $this->entries->save($this->validated($this->absence, '2026-09-16', 420));
        // Refusé : exclu du calcul (RG-TMP-4).
        $this->entries->save($this->rejected($this->alpha, '2026-09-15', 300));

        $report = $this->summary()->forUser($this->tenant, self::USER, $this->at('2026-09-16 09:00:00'), 4);

        self::assertSame(600, $report->productionMinutes); // 420 alpha + 180 beta
        self::assertSame(420, $report->absenceMinutes);
        self::assertSame(1020, $report->totalMinutes());
        // Trié par minutes décroissantes : Alpha (420) et Absence (420) devant Beta (180).
        self::assertSame($this->alpha, $report->byProject[0]->projectId);
        self::assertSame('Projet Alpha', $report->byProject[0]->label);
        self::assertSame(180, $report->byProject[2]->minutes);
        self::assertSame(600, $report->byType[ActivityType::PRODUCTION->value]);
        self::assertSame(420, $report->byType[ActivityType::ABSENCE->value]);
    }

    public function testEmptyWhenNoEntries(): void
    {
        $report = $this->summary()->forUser($this->tenant, self::USER, $this->at('2026-09-16 09:00:00'), 4);

        self::assertTrue($report->isEmpty());
        self::assertSame(0, $report->totalMinutes());
        self::assertSame(0.0, $report->occupationRate());
    }

    public function testOccupationRateUsesExpectedWorkingTime(): void
    {
        // 4 semaines glissantes depuis lundi : 420 min de production sur ~15 jours ouvrés attendus.
        $this->entries->save($this->validated($this->alpha, '2026-09-14', 420));

        $report = $this->summary()->forUser($this->tenant, self::USER, $this->at('2026-09-16 09:00:00'), 4);

        self::assertGreaterThan(0, $report->expectedMinutes);
        self::assertGreaterThan(0.0, $report->occupationRate());
        self::assertLessThan(1.0, $report->occupationRate());
    }

    private function summary(): ActivitySummary
    {
        return new ActivitySummary($this->entries, $this->projects);
    }

    private function validated(string $projectId, string $date, int $minutes): TimeEntry
    {
        $entry = new TimeEntry($this->tenant, self::USER, $projectId, $this->date($date), $minutes);
        $entry->validate('marc', $this->at($date.' 10:00:00'));

        return $entry;
    }

    private function submitted(string $projectId, string $date, int $minutes): TimeEntry
    {
        return new TimeEntry($this->tenant, self::USER, $projectId, $this->date($date), $minutes);
    }

    private function rejected(string $projectId, string $date, int $minutes): TimeEntry
    {
        $entry = new TimeEntry($this->tenant, self::USER, $projectId, $this->date($date), $minutes);
        $entry->reject('marc', 'incohérent', $this->at($date.' 10:00:00'));

        return $entry;
    }

    private function date(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value.' 00:00:00', new DateTimeZone('UTC'));
    }

    private function at(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value, new DateTimeZone('UTC'));
    }
}
