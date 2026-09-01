<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Absence;

use App\Domain\Absence\AbsenceRequest;
use App\Domain\Absence\AbsenceStatus;
use App\Domain\Tenant\TenantId;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * US-054 — une demande d'absence calcule sa durée à la demi-journée près, se valide/refuse, et
 * couvre les jours de sa plage. Aucune donnée médicale n'est portée par l'entité (HAB-3).
 */
final class AbsenceRequestTest extends TestCase
{
    private const string USER = '018f9c4e-0000-7000-8000-0000000000aa';
    private const string TYPE = '018f9c4e-0000-7000-8000-0000000000cc';

    public function testFullDayRangeCountsInclusiveDays(): void
    {
        // Du 01/09 matin au 05/09 soir = 5 jours pleins.
        $request = $this->request($this->date('2026-09-01'), $this->date('2026-09-05'), true, true);

        self::assertSame(5.0, $request->days());
        self::assertSame(AbsenceStatus::PENDING, $request->status());
    }

    public function testSingleHalfDayCountsHalf(): void
    {
        // 02/09 après-midi uniquement.
        $request = $this->request($this->date('2026-09-02'), $this->date('2026-09-02'), false, true);

        self::assertSame(0.5, $request->days());
    }

    public function testBoundaryHalfDaysAreDeducted(): void
    {
        // Du 01/09 après-midi au 03/09 matin = 3 - 0,5 - 0,5 = 2 jours.
        $request = $this->request($this->date('2026-09-01'), $this->date('2026-09-03'), false, false);

        self::assertSame(2.0, $request->days());
    }

    public function testCoversDaysWithinRange(): void
    {
        $request = $this->request($this->date('2026-09-01'), $this->date('2026-09-05'), true, true);

        self::assertTrue($request->coversDay($this->date('2026-09-03')));
        self::assertFalse($request->coversDay($this->date('2026-09-06')));
    }

    public function testValidateAndReject(): void
    {
        $request = $this->request($this->date('2026-09-01'), $this->date('2026-09-05'), true, true);
        $at = $this->at('2026-08-20 10:00:00');

        $request->validate('marc', $at);
        self::assertSame(AbsenceStatus::VALIDATED, $request->status());

        $request->reject('marc', 'Chevauchement livraison', $at);
        self::assertSame(AbsenceStatus::REJECTED, $request->status());
        self::assertSame('Chevauchement livraison', $request->rejectionReason());
    }

    public function testRejectRequiresReason(): void
    {
        $request = $this->request($this->date('2026-09-01'), $this->date('2026-09-05'), true, true);

        $this->expectException(InvalidArgumentException::class);
        $request->reject('marc', '   ', $this->at('2026-08-20 10:00:00'));
    }

    public function testZeroDurationIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // Un seul jour, ni matin ni après-midi → 0 jour.
        $this->request($this->date('2026-09-02'), $this->date('2026-09-02'), false, false);
    }

    public function testEndBeforeStartIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->request($this->date('2026-09-05'), $this->date('2026-09-01'), true, true);
    }

    private function request(DateTimeImmutable $from, DateTimeImmutable $to, bool $startsMorning, bool $endsAfternoon): AbsenceRequest
    {
        return new AbsenceRequest(
            TenantId::generate(),
            self::USER,
            self::TYPE,
            $from,
            $to,
            $startsMorning,
            $endsAfternoon,
            $this->at('2026-08-15 09:00:00'),
            'Vacances',
        );
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
