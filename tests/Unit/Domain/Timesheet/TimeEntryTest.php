<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Timesheet;

use App\Domain\Tenant\TenantId;
use App\Domain\Timesheet\TimeEntry;
use App\Domain\Timesheet\ValidationStatus;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * US-050 — l'imputation porte une durée en minutes entières (INV-2), strictement positive
 * et bornée à 24 h ; le commentaire vide est normalisé en null.
 */
final class TimeEntryTest extends TestCase
{
    public function testHoldsDurationInMinutes(): void
    {
        $entry = new TimeEntry(TenantId::generate(), 'user-1', 'project-1', new DateTimeImmutable('2026-09-15'), 210, '  demi-journée  ');

        self::assertSame(210, $entry->minutes());
        self::assertSame('demi-journée', $entry->comment());
    }

    public function testNormalizesEmptyCommentToNull(): void
    {
        $entry = new TimeEntry(TenantId::generate(), 'user-1', 'project-1', new DateTimeImmutable('2026-09-15'), 60, '   ');

        self::assertNull($entry->comment());
    }

    public function testRejectsNonPositiveDuration(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new TimeEntry(TenantId::generate(), 'user-1', 'project-1', new DateTimeImmutable('2026-09-15'), 0);
    }

    public function testRejectsDurationBeyondADay(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new TimeEntry(TenantId::generate(), 'user-1', 'project-1', new DateTimeImmutable('2026-09-15'), TimeEntry::MAX_MINUTES_PER_ENTRY + 1);
    }

    public function testReviseAdjustsDuration(): void
    {
        $entry = new TimeEntry(TenantId::generate(), 'user-1', 'project-1', new DateTimeImmutable('2026-09-15'), 120);

        $entry->reviseTo(180, 'ajusté');

        self::assertSame(180, $entry->minutes());
        self::assertSame('ajusté', $entry->comment());
    }

    public function testIsPendingByDefault(): void
    {
        $entry = new TimeEntry(TenantId::generate(), 'user-1', 'project-1', new DateTimeImmutable('2026-09-15'), 60);

        self::assertSame(ValidationStatus::PENDING, $entry->status());
    }

    public function testValidateAndReject(): void
    {
        $entry = new TimeEntry(TenantId::generate(), 'user-1', 'project-1', new DateTimeImmutable('2026-09-15'), 60);

        $entry->validate('chef', new DateTimeImmutable());
        self::assertSame(ValidationStatus::VALIDATED, $entry->status());

        $entry->reject('chef', 'temps non justifié', new DateTimeImmutable());
        self::assertSame(ValidationStatus::REJECTED, $entry->status());
        self::assertSame('temps non justifié', $entry->rejectionReason());
    }

    public function testRejectRequiresReason(): void
    {
        $entry = new TimeEntry(TenantId::generate(), 'user-1', 'project-1', new DateTimeImmutable('2026-09-15'), 60);

        $this->expectException(InvalidArgumentException::class);
        $entry->reject('chef', '   ', new DateTimeImmutable());
    }

    public function testRevisionReturnsEntryToPending(): void
    {
        $entry = new TimeEntry(TenantId::generate(), 'user-1', 'project-1', new DateTimeImmutable('2026-09-15'), 60);
        $entry->validate('chef', new DateTimeImmutable());

        $entry->reviseTo(90, null);

        self::assertSame(ValidationStatus::PENDING, $entry->status());
    }
}
