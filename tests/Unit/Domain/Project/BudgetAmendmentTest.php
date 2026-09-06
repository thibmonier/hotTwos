<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Project;

use App\Domain\Project\BudgetAmendment;
use App\Domain\Project\ProjectException;
use App\Domain\Tenant\TenantId;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

/**
 * US-033 (EF-PRJ-8) — invariants de l'avenant : au moins un delta non nul, motif obligatoire (RG-PRJ-4).
 */
final class BudgetAmendmentTest extends TestCase
{
    private const string PROJECT = '018f9c4e-0000-7000-8000-00000000aaaa';
    private const string AUTHOR = '018f9c4e-0000-7000-8000-0000000000c1';

    public function testRecordsDeltasAndReason(): void
    {
        $amendment = BudgetAmendment::record(TenantId::generate(), self::PROJECT, 20_000_00, 10_000_00, 'périmètre étendu — lot 3', self::AUTHOR, $this->now());

        self::assertSame(20_000_00, $amendment->deltaCostCents());
        self::assertSame(10_000_00, $amendment->deltaRevenueCents());
        self::assertSame('périmètre étendu — lot 3', $amendment->reason());
        self::assertSame(self::PROJECT, $amendment->projectId());
    }

    public function testNegativeDeltaIsAllowed(): void
    {
        $amendment = BudgetAmendment::record(TenantId::generate(), self::PROJECT, -5_000_00, 0, 'réduction de périmètre', self::AUTHOR, $this->now());

        self::assertSame(-5_000_00, $amendment->deltaCostCents());
        self::assertSame(0, $amendment->deltaRevenueCents());
    }

    public function testBothDeltasZeroIsRejected(): void
    {
        $this->expectException(ProjectException::class);
        BudgetAmendment::record(TenantId::generate(), self::PROJECT, 0, 0, 'sans effet', self::AUTHOR, $this->now());
    }

    public function testEmptyReasonIsRejected(): void
    {
        $this->expectException(ProjectException::class);
        BudgetAmendment::record(TenantId::generate(), self::PROJECT, 1_000_00, 0, '   ', self::AUTHOR, $this->now());
    }

    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('2027-01-10 09:00:00', new DateTimeZone('UTC'));
    }
}
