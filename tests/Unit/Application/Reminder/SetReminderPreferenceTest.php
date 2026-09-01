<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Reminder;

use App\Application\Reminder\SetReminderPreference;
use App\Domain\Tenant\TenantId;
use App\Domain\User\User;
use App\Tests\Support\Authorization\RecordingSecurityAuditLogger;
use App\Tests\Support\Reminder\InMemoryReminderPreferenceRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use DateTimeImmutable;
use DateTimeZone;

/**
 * US-056 (T-056-07, revue) — logique d'opt-out/opt-in : un collaborateur agit sur sa **propre**
 * préférence (créée à la volée), la bascule est idempotente sur une même ligne, et chaque décision
 * est tracée (RGPD).
 */
final class SetReminderPreferenceTest extends TestCase
{
    private TenantId $tenant;
    private User $user;
    private InMemoryReminderPreferenceRepository $preferences;
    private RecordingSecurityAuditLogger $audit;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $this->user = new User($this->tenant, 'camille@agence.test', 'x');
        $this->preferences = new InMemoryReminderPreferenceRepository();
        $this->audit = new RecordingSecurityAuditLogger();
    }

    public function testCurrentDefaultsToOptedInWhenNoPreference(): void
    {
        self::assertFalse($this->service()->current($this->user)->isOptedOut());
    }

    public function testOptOutCreatesPreferenceAndAudits(): void
    {
        $preference = $this->service()->optOut($this->user);

        self::assertTrue($preference->isOptedOut());
        self::assertSame($this->user->id(), $preference->userId());
        self::assertCount(1, $this->preferences->preferences);
        self::assertTrue($this->audit->has('reminder_opt_out'));
    }

    public function testOptInAfterOptOutUpdatesTheSameRow(): void
    {
        $service = $this->service();
        $service->optOut($this->user);
        $service->optIn($this->user);

        self::assertCount(1, $this->preferences->preferences, 'La bascule met à jour la même préférence.');
        self::assertFalse($this->preferences->preferences[0]->isOptedOut());
        self::assertTrue($this->audit->has('reminder_opt_in'));
    }

    private function service(): SetReminderPreference
    {
        return new SetReminderPreference(
            $this->preferences,
            $this->audit,
            new MockClock(new DateTimeImmutable('2026-09-02 09:00:00', new DateTimeZone('UTC'))),
        );
    }
}
