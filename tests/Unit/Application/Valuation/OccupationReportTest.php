<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Valuation;

use App\Application\Valuation\OccupationReport;
use App\Domain\Absence\AbsenceRequest;
use App\Domain\Tenant\TenantId;
use App\Tests\Support\Absence\InMemoryAbsenceRequestRepository;
use App\Tests\Support\Valuation\InMemoryTimeEntryValuationRepository;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

/**
 * US-060 (T-060-03) — occupation par collaborateur = jours valorisés / (jours ouvrés − absences),
 * sur le mois de la prestation valorisée la plus récente.
 */
final class OccupationReportTest extends TestCase
{
    private const string ALICE = '018f9c4e-0000-7000-8000-0000000000a1';
    private const string BOB = '018f9c4e-0000-7000-8000-0000000000b2';
    private const string TYPE = '018f9c4e-0000-7000-8000-0000000000f0';

    private TenantId $tenant;
    private InMemoryTimeEntryValuationRepository $valuations;
    private InMemoryAbsenceRequestRepository $absences;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $this->valuations = new InMemoryTimeEntryValuationRepository();
        $this->absences = new InMemoryAbsenceRequestRepository();
    }

    public function testOccupationPerCollaboratorOnReferenceMonth(): void
    {
        // Mois de référence = mois de la dernière prestation valorisée (août 2026).
        $this->valuations->latestValuedWorkDate = $this->date('2026-08-31');
        $this->valuations->valuedDayCountByUser = [self::ALICE => 18, self::BOB => 10];

        // Bob a 5 jours ouvrés d'absence validée en août (lun-ven complets).
        $this->absences->save($this->validatedAbsence(self::BOB, '2026-08-10', '2026-08-14'));

        $overview = $this->report()->forTenant($this->tenant);

        self::assertSame('2026-08', $overview->referenceMonth);
        // Seuls les collaborateurs avec activité valorisée sont listés (Alice, Bob).
        self::assertCount(2, $overview->lines);

        $lines = [];
        foreach ($overview->lines as $line) {
            $lines[$line->userId] = $line;
        }

        $workingDays = $this->weekdaysInMonth('2026-08');
        self::assertSame($workingDays, $lines[self::ALICE]->capacityDays, 'Sans absence, capacité = jours ouvrés du mois.');
        self::assertSame($workingDays - 5, $lines[self::BOB]->capacityDays, '5 jours ouvrés d\'absence réduisent la capacité.');
        self::assertSame(18, $lines[self::ALICE]->valuedDays);
        self::assertSame((int) min(100, round(18 / $workingDays * 100)), $lines[self::ALICE]->percent());
    }

    public function testSortedByOccupationDescending(): void
    {
        $this->valuations->latestValuedWorkDate = $this->date('2026-08-31');
        // Bob plus occupé qu'Alice.
        $this->valuations->valuedDayCountByUser = [self::ALICE => 5, self::BOB => 20];

        $overview = $this->report()->forTenant($this->tenant);

        self::assertSame(self::BOB, $overview->lines[0]->userId);
        self::assertSame(self::ALICE, $overview->lines[1]->userId);
    }

    public function testEmptyWhenNoValuedActivity(): void
    {
        // Aucune valorisation → mois courant (clock) en repli, aucune ligne.
        $this->valuations->valuedDayCountByUser = [];

        $overview = $this->report()->forTenant($this->tenant);

        self::assertTrue($overview->isEmpty());
        self::assertSame('2026-09', $overview->referenceMonth);
    }

    public function testOccupationIsCappedAtHundredPercent(): void
    {
        $this->valuations->latestValuedWorkDate = $this->date('2026-08-31');
        // Plus de jours valorisés que la capacité (reports / week-ends) → borné à 100 %.
        $this->valuations->valuedDayCountByUser = [self::ALICE => 999];

        $overview = $this->report()->forTenant($this->tenant);

        self::assertSame(100, $overview->lines[0]->percent());
    }

    private function report(): OccupationReport
    {
        return new OccupationReport(
            $this->valuations,
            $this->absences,
            new MockClock(new DateTimeImmutable('2026-09-15 10:00:00', new DateTimeZone('UTC'))),
        );
    }

    private function validatedAbsence(string $userId, string $from, string $to): AbsenceRequest
    {
        $absence = new AbsenceRequest(
            $this->tenant,
            $userId,
            self::TYPE,
            $this->date($from),
            $this->date($to),
            true,
            true,
            $this->date($from),
        );
        $absence->validate('018f9c4e-0000-7000-8000-0000000000ff', $this->date($from));

        return $absence;
    }

    private function weekdaysInMonth(string $month): int
    {
        $from = new DateTimeImmutable($month.'-01 00:00:00', new DateTimeZone('UTC'));
        $to = $from->modify('+1 month');
        $count = 0;
        for ($day = $from; $day < $to; $day = $day->modify('+1 day')) {
            if ((int) $day->format('N') <= 5) {
                ++$count;
            }
        }

        return $count;
    }

    private function date(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value.' 00:00:00', new DateTimeZone('UTC'));
    }
}
