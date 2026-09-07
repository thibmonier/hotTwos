<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Calendar;

use App\Domain\Calendar\Holiday;
use App\Domain\Calendar\ClosurePeriod;
use App\Domain\Calendar\WorkSchedule;
use App\Domain\Calendar\WorkingDaysCalculator;
use App\Domain\Tenant\TenantId;
use App\Tests\Support\Calendar\InMemoryHolidayRepository;
use App\Tests\Support\Calendar\InMemoryClosurePeriodRepository;
use App\Tests\Support\Calendar\InMemoryWorkScheduleRepository;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

/**
 * US-012 (EF-REF-6) — calcul unifié des jours ouvrés : exclusion des week-ends ET des jours fériés du
 * tenant, avec isolation multi-tenant.
 */
final class WorkingDaysCalculatorTest extends TestCase
{
    private const string USER = '018f9c4e-0000-7000-8000-0000000000a1';

    private TenantId $tenant;
    private InMemoryHolidayRepository $holidays;
    private InMemoryClosurePeriodRepository $closures;
    private InMemoryWorkScheduleRepository $schedules;
    private WorkingDaysCalculator $calculator;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $this->holidays = new InMemoryHolidayRepository();
        $this->closures = new InMemoryClosurePeriodRepository();
        $this->schedules = new InMemoryWorkScheduleRepository();
        $this->calculator = new WorkingDaysCalculator($this->holidays, $this->closures, $this->schedules);
    }

    public function testUserWorkScheduleReducesWorkingDays(): void
    {
        // Régime temps partiel : Lun-Jeu (pas le vendredi) → 4 jours ouvrés sur une semaine pleine.
        $this->schedules->save(new WorkSchedule($this->tenant, self::USER, [1, 2, 3, 4]));

        $count = $this->calculator->workingDaysForUser($this->tenant, self::USER, $this->date('2026-07-06'), $this->date('2026-07-13'));

        self::assertSame(4, $count);
        self::assertFalse($this->calculator->isWorkingDayForUser($this->tenant, self::USER, $this->date('2026-07-10'))); // vendredi non travaillé
    }

    public function testUserWithoutScheduleIsFullTime(): void
    {
        // Sans régime : temps plein Lun-Ven, mais fériés/fermetures s'appliquent.
        $this->holidays->save(new Holiday($this->tenant, $this->date('2026-07-14'), 'Fête nationale'));

        self::assertSame(5, $this->calculator->workingDaysForUser($this->tenant, self::USER, $this->date('2026-07-06'), $this->date('2026-07-13')));
        self::assertSame(4, $this->calculator->workingDaysForUser($this->tenant, self::USER, $this->date('2026-07-13'), $this->date('2026-07-20')));
    }

    public function testExcludesClosurePeriods(): void
    {
        // Fermeture du mardi 14/07 au mercredi 15/07 → 3 jours ouvrés sur la semaine 13→19.
        $this->closures->save(new ClosurePeriod($this->tenant, $this->date('2026-07-14'), $this->date('2026-07-15'), 'Pont'));

        $count = $this->calculator->workingDaysBetween($this->tenant, $this->date('2026-07-13'), $this->date('2026-07-20'));

        self::assertSame(3, $count);
        self::assertFalse($this->calculator->isWorkingDay($this->tenant, $this->date('2026-07-14')));
    }

    public function testExcludesWeekends(): void
    {
        // Semaine du lundi 06/07/2026 au dimanche 12/07 → 5 jours ouvrés.
        $count = $this->calculator->workingDaysBetween($this->tenant, $this->date('2026-07-06'), $this->date('2026-07-13'));

        self::assertSame(5, $count);
    }

    public function testExcludesHolidays(): void
    {
        // Le 14/07/2026 (mardi) est férié → 4 jours ouvrés sur la semaine 13→19.
        $this->holidays->save(new Holiday($this->tenant, $this->date('2026-07-14'), 'Fête nationale'));

        $count = $this->calculator->workingDaysBetween($this->tenant, $this->date('2026-07-13'), $this->date('2026-07-20'));

        self::assertSame(4, $count);
    }

    public function testIsWorkingDayFalseOnWeekendAndHoliday(): void
    {
        $this->holidays->save(new Holiday($this->tenant, $this->date('2026-07-14'), 'Fête nationale'));

        self::assertTrue($this->calculator->isWorkingDay($this->tenant, $this->date('2026-07-13')));  // lundi
        self::assertFalse($this->calculator->isWorkingDay($this->tenant, $this->date('2026-07-14'))); // férié
        self::assertFalse($this->calculator->isWorkingDay($this->tenant, $this->date('2026-07-11'))); // samedi
    }

    public function testHolidaysAreIsolatedPerTenant(): void
    {
        $other = TenantId::generate();
        // Férié déclaré pour un AUTRE tenant : sans effet ici.
        $this->holidays->save(new Holiday($other, $this->date('2026-07-14'), 'Autre tenant'));

        self::assertTrue($this->calculator->isWorkingDay($this->tenant, $this->date('2026-07-14')));
        self::assertSame(5, $this->calculator->workingDaysBetween($this->tenant, $this->date('2026-07-13'), $this->date('2026-07-20')));
    }

    private function date(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value.' 00:00:00', new DateTimeZone('UTC'));
    }
}
