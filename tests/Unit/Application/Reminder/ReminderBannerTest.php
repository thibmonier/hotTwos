<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Reminder;

use App\Application\Completeness\CompletenessGrid;
use App\Application\Reminder\ReminderBanner;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Tests\Support\Absence\InMemoryAbsenceRequestRepository;
use App\Tests\Support\Reminder\InMemoryReminderPreferenceRepository;
use App\Tests\Support\Timesheet\InMemoryTimeEntryRepository;
use App\Domain\Reminder\ReminderPreference;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use DateTimeImmutable;
use DateTimeZone;

/**
 * US-056 (T-056-05) — le bandeau de rappel n'apparaît que pour un collaborateur en opt-out et en
 * retard : il ne reçoit plus de relance poussée mais reste informé dans son écran de saisie.
 */
final class ReminderBannerTest extends TestCase
{
    private TenantId $tenant;
    private User $user;
    private InMemoryReminderPreferenceRepository $preferences;
    private InMemoryTimeEntryRepository $entries;
    private InMemoryAbsenceRequestRepository $absences;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $this->user = new User($this->tenant, 'camille@agence.test', 'x');
        $this->preferences = new InMemoryReminderPreferenceRepository();
        $this->entries = new InMemoryTimeEntryRepository();
        $this->absences = new InMemoryAbsenceRequestRepository();
    }

    public function testNoBannerWhenNotOptedOut(): void
    {
        // Aucune préférence enregistrée : le collaborateur reçoit les relances, pas de bandeau.
        self::assertSame(0, $this->banner()->lateWeeksForOptedOut($this->user));
    }

    public function testCountsLateWeeksWhenOptedOut(): void
    {
        $this->preferences->save(new ReminderPreference($this->tenant, $this->user->id(), true, $this->at('2026-08-01 09:00:00')));

        // Aucune imputation : les semaines passées (au-delà de l'échéance J+2) sont en retard.
        $lateWeeks = $this->banner()->lateWeeksForOptedOut($this->user);

        self::assertGreaterThan(0, $lateWeeks);
    }

    private function banner(): ReminderBanner
    {
        return new ReminderBanner(
            $this->preferences,
            new CompletenessGrid($this->entries, $this->absences),
            new MockClock($this->at('2026-09-30 09:00:00')),
        );
    }

    private function at(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value, new DateTimeZone('UTC'));
    }
}
