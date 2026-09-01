<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Analytics;

use App\Domain\Analytics\RevenueRecognized;
use App\Domain\Tenant\TenantId;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * US-005 — l'événement de sonde porte une charge utile stable et refuse une période mal formée.
 */
final class RevenueRecognizedTest extends TestCase
{
    public function testExposesStablePayload(): void
    {
        $event = new RevenueRecognized(TenantId::generate(), '2026-08', 'PRJ-42', 120050, new DateTimeImmutable('2026-08-15'));

        self::assertSame('revenue_recognized', $event->name());
        self::assertSame([
            'period' => '2026-08',
            'project_ref' => 'PRJ-42',
            'amount_cents' => 120050,
        ], $event->payload());
    }

    public function testCarriesSourceTimeEntryWhenProvided(): void
    {
        $event = new RevenueRecognized(TenantId::generate(), '2026-08', 'PRJ-42', 78000, new DateTimeImmutable('2026-08-15'), 'entry-1');

        self::assertSame([
            'period' => '2026-08',
            'project_ref' => 'PRJ-42',
            'amount_cents' => 78000,
            'source_time_entry_id' => 'entry-1',
        ], $event->payload());
    }

    public function testRejectsMalformedPeriod(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RevenueRecognized(TenantId::generate(), '2026-13', 'PRJ-42', 100, new DateTimeImmutable());
    }

    public function testRejectsEmptyProjectRef(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RevenueRecognized(TenantId::generate(), '2026-08', '   ', 100, new DateTimeImmutable());
    }
}
