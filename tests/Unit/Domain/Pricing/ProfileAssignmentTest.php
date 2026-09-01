<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Pricing;

use App\Domain\Pricing\ProfileAssignment;
use App\Domain\Shared\EffectivePeriod;
use App\Domain\Tenant\TenantId;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

/**
 * US-011/US-060 — rattachement d'un collaborateur à un profil de tarification, historisé à date
 * d'effet. C'est le pivot de la valorisation : il détermine le profil (donc le tarif) applicable.
 */
final class ProfileAssignmentTest extends TestCase
{
    private const string TENANT = '018f9c4e-0000-7000-8000-000000000001';
    private const string USER = '018f9c4e-0000-7000-8000-0000000000aa';
    private const string PROFILE = '018f9c4e-0000-7000-8000-0000000000cc';

    public function testAssignmentCarriesUserProfileAndPeriod(): void
    {
        $assignment = new ProfileAssignment(
            TenantId::fromString(self::TENANT),
            self::USER,
            self::PROFILE,
            EffectivePeriod::since($this->date('2026-01-01')),
        );

        self::assertSame(self::TENANT, $assignment->tenantId()->toString());
        self::assertSame(self::USER, $assignment->userId());
        self::assertSame(self::PROFILE, $assignment->profileId());
        self::assertTrue($assignment->period()->contains($this->date('2026-06-01')));
    }

    private function date(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value.' 00:00:00', new DateTimeZone('UTC'));
    }
}
