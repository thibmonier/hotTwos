<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Organization;

use App\Domain\Organization\OrgMembership;
use App\Domain\Shared\EffectivePeriod;
use App\Domain\Tenant\TenantId;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use DateTimeZone;

/**
 * US-010 (EF-REF-2, RG-REF-1) — rattachement historisé d'un collaborateur à une unité,
 * daté à l'effet. La période de validité est portée par le VO {@see EffectivePeriod}.
 */
final class OrgMembershipTest extends TestCase
{
    private const string TENANT = '018f9c4e-0000-7000-8000-000000000001';
    private const string USER = '018f9c4e-0000-7000-8000-0000000000aa';
    private const string UNIT = '018f9c4e-0000-7000-8000-0000000000bb';

    public function testMembershipCarriesTenantUserUnitAndPeriod(): void
    {
        $period = EffectivePeriod::since($this->date('2026-01-01'));
        $membership = new OrgMembership(TenantId::fromString(self::TENANT), self::USER, self::UNIT, $period);

        self::assertSame(self::TENANT, $membership->tenantId()->toString());
        self::assertSame(self::USER, $membership->userId());
        self::assertSame(self::UNIT, $membership->orgUnitId());
        self::assertTrue($membership->period()->isOpenEnded());
    }

    public function testPeriodIsReconstructedFromStoredBounds(): void
    {
        $period = EffectivePeriod::between($this->date('2026-01-01'), $this->date('2026-07-01'));
        $membership = new OrgMembership(TenantId::fromString(self::TENANT), self::USER, self::UNIT, $period);

        self::assertFalse($membership->period()->isOpenEnded());
        self::assertTrue($membership->period()->equals($period));
    }

    private function date(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value.' 00:00:00', new DateTimeZone('UTC'));
    }
}
