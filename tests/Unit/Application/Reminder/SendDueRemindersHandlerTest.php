<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Reminder;

use App\Application\Completeness\CompletenessGrid;
use App\Application\Reminder\Message\SendDueReminders;
use App\Application\Reminder\ScheduleReminders;
use App\Application\Reminder\SendDueRemindersHandler;
use App\Domain\Reminder\ReminderRule;
use App\Domain\Tenant\TenantId;
use App\Tests\Support\Absence\InMemoryAbsenceRequestRepository;
use App\Tests\Support\Reminder\InMemoryReminderLogRepository;
use App\Tests\Support\Reminder\InMemoryReminderPreferenceRepository;
use App\Tests\Support\Reminder\InMemoryReminderRuleRepository;
use App\Tests\Support\Reminder\RecordingReminderNotifier;
use App\Tests\Support\Timesheet\InMemoryTimeEntryRepository;
use App\Tests\Support\User\InMemoryUserRepository;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;
use DateTimeZone;

/**
 * US-056 (T-056-06) — à la consommation, le handler journalise **et** notifie chaque relance décidée
 * par le moteur, en horodatant à l'instant du message. Sans relance due, il n'écrit ni ne notifie.
 */
final class SendDueRemindersHandlerTest extends TestCase
{
    private const string USER = '018f9c4e-0000-7000-8000-0000000000aa';

    private TenantId $tenant;
    private InMemoryReminderRuleRepository $rules;
    private InMemoryReminderPreferenceRepository $preferences;
    private InMemoryReminderLogRepository $logs;
    private InMemoryUserRepository $users;
    private InMemoryTimeEntryRepository $entries;
    private InMemoryAbsenceRequestRepository $absences;
    private RecordingReminderNotifier $notifier;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $this->rules = new InMemoryReminderRuleRepository();
        $this->preferences = new InMemoryReminderPreferenceRepository();
        $this->logs = new InMemoryReminderLogRepository();
        $this->users = new InMemoryUserRepository();
        $this->entries = new InMemoryTimeEntryRepository();
        $this->absences = new InMemoryAbsenceRequestRepository();
        $this->notifier = new RecordingReminderNotifier();

        $this->users->register($this->tenant, self::USER);
        $this->rules->save(ReminderRule::default($this->tenant));
    }

    public function testJournalizesAndNotifiesEachDueReminder(): void
    {
        $now = $this->at('2026-09-02 06:00:00');
        $this->handler()(new SendDueReminders($this->tenant->toString(), $now));

        self::assertNotEmpty($this->logs->logs, 'Au moins une relance doit être journalisée.');
        self::assertSameSize($this->logs->logs, $this->notifier->sent, 'Une notification par relance journalisée.');

        $log = $this->logs->logs[0];
        self::assertSame(self::USER, $log->userId());
        self::assertSame(1, $log->sequence());
        self::assertEquals($now, $log->sentAt());
    }

    public function testDisabledTenantWritesNothing(): void
    {
        $rule = ReminderRule::default($this->tenant);
        $rule->deactivate();
        $this->rules = new InMemoryReminderRuleRepository();
        $this->rules->save($rule);

        $this->handler()(new SendDueReminders($this->tenant->toString(), $this->at('2026-09-02 06:00:00')));

        self::assertSame([], $this->logs->logs);
        self::assertSame([], $this->notifier->sent);
    }

    private function handler(): SendDueRemindersHandler
    {
        $engine = new ScheduleReminders(
            $this->rules,
            $this->preferences,
            $this->logs,
            $this->users,
            new CompletenessGrid($this->entries, $this->absences),
        );

        return new SendDueRemindersHandler($engine, $this->logs, $this->notifier);
    }

    private function at(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value, new DateTimeZone('UTC'));
    }
}
