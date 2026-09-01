<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Period;

use App\Domain\Period\AccountingPeriod;
use App\Domain\Period\PeriodStatus;
use App\Domain\Tenant\TenantId;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * US-057 — une période comptable naît ouverte, se clôture en figeant auteur/horodatage, et
 * refuse un format de mois mal formé.
 */
final class AccountingPeriodTest extends TestCase
{
    private const string ACTOR = '018f9c4e-0000-7000-8000-0000000000aa';

    public function testStartsOpen(): void
    {
        $period = new AccountingPeriod(TenantId::generate(), '2026-08');

        self::assertSame(PeriodStatus::OPEN, $period->status());
        self::assertFalse($period->isClosed());
        self::assertNull($period->closedAt());
    }

    public function testCloseFreezesAuthorAndTimestamp(): void
    {
        $period = new AccountingPeriod(TenantId::generate(), '2026-08');
        $at = new DateTimeImmutable('2026-09-01 10:00:00', new DateTimeZone('UTC'));

        $period->close(self::ACTOR, $at);

        self::assertTrue($period->isClosed());
        self::assertSame(self::ACTOR, $period->closedBy());
        self::assertEquals($at, $period->closedAt());
    }

    public function testCloseIsIdempotent(): void
    {
        $period = new AccountingPeriod(TenantId::generate(), '2026-08');
        $first = new DateTimeImmutable('2026-09-01 10:00:00', new DateTimeZone('UTC'));
        $later = new DateTimeImmutable('2026-09-05 10:00:00', new DateTimeZone('UTC'));

        $period->close(self::ACTOR, $first);
        $period->close('other-actor', $later);

        // Une période déjà clôturée n'est pas re-clôturée : l'auteur/horodatage d'origine tiennent.
        self::assertSame(self::ACTOR, $period->closedBy());
        self::assertEquals($first, $period->closedAt());
    }

    public function testRejectsMalformedPeriod(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AccountingPeriod(TenantId::generate(), '2026-13');
    }
}
