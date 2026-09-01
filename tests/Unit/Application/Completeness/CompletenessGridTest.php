<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Completeness;

use App\Application\Completeness\CompletenessGrid;
use App\Domain\Absence\AbsenceRequest;
use App\Domain\Completeness\CompletenessState;
use App\Domain\Completeness\WeekCompleteness;
use App\Domain\Tenant\TenantId;
use App\Domain\Timesheet\TimeEntry;
use App\Tests\Support\Absence\InMemoryAbsenceRequestRepository;
use App\Tests\Support\Timesheet\InMemoryTimeEntryRepository;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

/**
 * US-058 (T-058-01, CA-1) — la grille dérive l'état de complétude (soumise/partielle/vide en
 * retard/en cours) du taux de jours ouvrés saisis vs attendus, déduction faite des absences validées.
 */
final class CompletenessGridTest extends TestCase
{
    private const string USER = '018f9c4e-0000-7000-8000-0000000000aa';
    private const string PROJECT = '018f9c4e-0000-7000-8000-0000000000bb';
    private const string TYPE = '018f9c4e-0000-7000-8000-0000000000cc';

    private TenantId $tenant;
    private InMemoryTimeEntryRepository $entries;
    private InMemoryAbsenceRequestRepository $absences;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $this->entries = new InMemoryTimeEntryRepository();
        $this->absences = new InMemoryAbsenceRequestRepository();
    }

    public function testDerivesStatesAcrossWeeks(): void
    {
        // Semaine complète (31/08 → 04/09) : 5 jours saisis.
        foreach (['2026-08-31', '2026-09-01', '2026-09-02', '2026-09-03', '2026-09-04'] as $d) {
            $this->fill($this->date($d));
        }
        // Semaine partielle (07/09 → 08/09) : 2 jours, délai dépassé.
        $this->fill($this->date('2026-09-07'));
        $this->fill($this->date('2026-09-08'));

        // « Maintenant » = mercredi 16/09/2026.
        $grid = $this->grid()->build($this->tenant, [self::USER], $this->at('2026-09-16 09:00:00'), 4);

        self::assertSame(CompletenessState::SUBMITTED, $this->state($grid, '2026-08-31'));
        self::assertSame(CompletenessState::EMPTY_LATE, $this->state($grid, '2026-08-24')); // vide, J+2 dépassé
        self::assertSame(CompletenessState::PARTIAL, $this->state($grid, '2026-09-07'));
        self::assertSame(CompletenessState::IN_PROGRESS, $this->state($grid, '2026-09-14')); // semaine en cours, délai non atteint
    }

    public function testFullWeekAbsenceExpectsNothing(): void
    {
        // Absence validée toute la semaine 31/08 → 04/09 : rien n'est attendu → soumise.
        $absence = new AbsenceRequest($this->tenant, self::USER, self::TYPE, $this->date('2026-08-31'), $this->date('2026-09-04'), true, true, $this->at('2026-08-01 09:00:00'));
        $absence->validate('marc', $this->at('2026-08-20 09:00:00'));
        $this->absences->save($absence);

        $grid = $this->grid()->build($this->tenant, [self::USER], $this->at('2026-09-16 09:00:00'), 4);
        $week = $this->cell($grid, '2026-08-31');

        self::assertSame(0, $week->expectedDays);
        self::assertSame(CompletenessState::SUBMITTED, $week->state);
    }

    private function grid(): CompletenessGrid
    {
        return new CompletenessGrid($this->entries, $this->absences);
    }

    private function fill(DateTimeImmutable $day): void
    {
        $this->entries->save(new TimeEntry($this->tenant, self::USER, self::PROJECT, $day, 420));
    }

    /**
     * @param list<WeekCompleteness> $grid
     */
    private function state(array $grid, string $mondayIso): CompletenessState
    {
        return $this->cell($grid, $mondayIso)->state;
    }

    /**
     * @param list<WeekCompleteness> $grid
     */
    private function cell(array $grid, string $mondayIso): WeekCompleteness
    {
        foreach ($grid as $week) {
            if ($week->weekStart->format('Y-m-d') === $mondayIso) {
                return $week;
            }
        }

        self::fail(sprintf('Semaine %s absente de la grille.', $mondayIso));
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
