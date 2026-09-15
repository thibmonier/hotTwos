<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Absence;

use App\Application\Absence\AbsenceBalance;
use App\Domain\Absence\AbsenceRequest;
use App\Domain\Calendar\WorkingDaysCalculator;
use App\Domain\Tenant\TenantId;
use App\Tests\Support\Absence\InMemoryAbsenceRequestRepository;
use App\Tests\Support\Calendar\InMemoryClosurePeriodRepository;
use App\Tests\Support\Calendar\InMemoryHolidayRepository;
use App\Tests\Support\Calendar\InMemoryWorkScheduleRepository;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class AbsenceBalanceTest extends TestCase
{
    private const string USER = '018f9c4e-0000-7000-8000-0000000000aa';
    private const string TYPE = '018f9c4e-0000-7000-8000-0000000000cc';

    public function testCountsTakenPendingAndProjectsBalance(): void
    {
        $tenant = TenantId::generate();
        $requests = new InMemoryAbsenceRequestRepository();

        // Toutes les plages sont Lun→Ven (pas de week-end) : ouvrés = calendaires.
        $requests->save($this->decided($tenant, $this->date('2026-03-02'), $this->date('2026-03-06'), true));
        $requests->save($this->decided($tenant, $this->date('2026-04-06'), $this->date('2026-04-10'), true));
        $requests->save($this->pending($tenant, $this->date('2026-09-01'), $this->date('2026-09-03')));
        $requests->save($this->decided($tenant, $this->date('2026-10-05'), $this->date('2026-10-08'), false));

        $counters = new AbsenceBalance($requests, $this->calculator(), 25.0)->for($tenant, self::USER);

        self::assertSame(25.0, $counters->acquired);
        self::assertSame(10.0, $counters->taken);
        self::assertSame(3.0, $counters->pending);
        self::assertSame(15.0, $counters->balance());          // 25 - 10
        self::assertSame(12.0, $counters->projectedBalance()); // 15 - 3
    }

    /**
     * US-107 — le décompte est en **jours ouvrés** : une plage franchissant un week-end
     * ne consomme que ses jours ouvrés (vendredi + lundi = 2), pas les 4 jours calendaires.
     */
    public function testWeekendDaysDoNotConsumeBalance(): void
    {
        $tenant = TenantId::generate();
        $requests = new InMemoryAbsenceRequestRepository();

        // Ven 2026-03-06 → Lun 2026-03-09 : 4 jours calendaires, 2 jours ouvrés (Ven + Lun).
        $requests->save($this->decided($tenant, $this->date('2026-03-06'), $this->date('2026-03-09'), true));

        $counters = new AbsenceBalance($requests, $this->calculator(), 25.0)->for($tenant, self::USER);

        self::assertSame(2.0, $counters->taken, 'Le week-end ne doit pas décompter de solde.');
        self::assertSame(23.0, $counters->balance());
    }

    public function testEmptyUserHasFullBalance(): void
    {
        $tenant = TenantId::generate();
        $counters = new AbsenceBalance(new InMemoryAbsenceRequestRepository(), $this->calculator(), 25.0)->for($tenant, self::USER);

        self::assertSame(0.0, $counters->taken);
        self::assertSame(25.0, $counters->balance());
    }

    private function calculator(): WorkingDaysCalculator
    {
        // Repos vides : ni férié, ni fermeture, ni régime ⇒ jours ouvrés = Lun-Ven.
        return new WorkingDaysCalculator(
            new InMemoryHolidayRepository(),
            new InMemoryClosurePeriodRepository(),
            new InMemoryWorkScheduleRepository(),
        );
    }

    private function pending(TenantId $tenant, DateTimeImmutable $from, DateTimeImmutable $to): AbsenceRequest
    {
        return new AbsenceRequest($tenant, self::USER, self::TYPE, $from, $to, true, true, $this->at());
    }

    private function decided(TenantId $tenant, DateTimeImmutable $from, DateTimeImmutable $to, bool $validated): AbsenceRequest
    {
        $request = $this->pending($tenant, $from, $to);
        if ($validated) {
            $request->validate('marc', $this->at());
        } else {
            $request->reject('marc', 'motif', $this->at());
        }

        return $request;
    }

    private function date(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value.' 00:00:00', new DateTimeZone('UTC'));
    }

    private function at(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-02-01 09:00:00', new DateTimeZone('UTC'));
    }
}
