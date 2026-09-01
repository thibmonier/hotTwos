<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Reminder;

use App\Application\Completeness\CompletenessGrid;
use App\Application\Reminder\ReminderDecision;
use App\Application\Reminder\ScheduleReminders;
use App\Domain\Reminder\ReminderChannel;
use App\Domain\Reminder\ReminderLog;
use App\Domain\Reminder\ReminderPreference;
use App\Domain\Reminder\ReminderRule;
use App\Domain\Tenant\TenantId;
use App\Domain\Timesheet\TimeEntry;
use App\Tests\Support\Absence\InMemoryAbsenceRequestRepository;
use App\Tests\Support\Reminder\InMemoryReminderLogRepository;
use App\Tests\Support\Reminder\InMemoryReminderPreferenceRepository;
use App\Tests\Support\Reminder\InMemoryReminderRuleRepository;
use App\Tests\Support\Timesheet\InMemoryTimeEntryRepository;
use App\Tests\Support\User\InMemoryUserRepository;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

/**
 * US-056 (T-056-02 / T-056-06) — le moteur planifie des relances bornées et déterministes :
 * délai initial, plancher anti-spam, escalade, arrêt à la soumission, opt-out et désactivation
 * globale du tenant. Semaine cible : lundi 24/08/2026 (échéance J+2 le 01/09, première relance
 * possible le 02/09 avec le délai initial d'un jour).
 */
final class ScheduleRemindersTest extends TestCase
{
    private const string USER = '018f9c4e-0000-7000-8000-0000000000aa';
    private const string PROJECT = '018f9c4e-0000-7000-8000-0000000000bb';
    private const string WEEK = '2026-08-24';

    private TenantId $tenant;
    private InMemoryReminderRuleRepository $rules;
    private InMemoryReminderPreferenceRepository $preferences;
    private InMemoryReminderLogRepository $logs;
    private InMemoryUserRepository $users;
    private InMemoryTimeEntryRepository $entries;
    private InMemoryAbsenceRequestRepository $absences;

    protected function setUp(): void
    {
        $this->tenant = TenantId::generate();
        $this->rules = new InMemoryReminderRuleRepository();
        $this->preferences = new InMemoryReminderPreferenceRepository();
        $this->logs = new InMemoryReminderLogRepository();
        $this->users = new InMemoryUserRepository();
        $this->entries = new InMemoryTimeEntryRepository();
        $this->absences = new InMemoryAbsenceRequestRepository();

        $this->users->register($this->tenant, self::USER);
        $this->rules->save(ReminderRule::default($this->tenant));
    }

    public function testFirstReminderIsDueAfterInitialDelay(): void
    {
        // Mercredi 02/09 : échéance (01/09) + délai initial (1 j) atteinte, semaine 24/08 vide.
        $decision = $this->decisionFor($this->plan('2026-09-02 09:00:00'), self::WEEK);

        self::assertInstanceOf(ReminderDecision::class, $decision);
        self::assertSame(1, $decision->sequence);
        self::assertFalse($decision->escalated);
        self::assertSame(ReminderChannel::IN_APP, $decision->channel);
    }

    public function testNoReminderBeforeInitialDelay(): void
    {
        // Mardi 01/09 : la semaine est en retard mais le délai initial (→ 02/09) n'est pas atteint.
        self::assertNull($this->decisionFor($this->plan('2026-09-01 09:00:00'), self::WEEK));
    }

    public function testSubmittedWeekStopsReminders(): void
    {
        $this->fillWeek(self::WEEK); // 5 jours saisis → soumise (CA-3)

        self::assertNull($this->decisionFor($this->plan('2026-09-02 09:00:00'), self::WEEK));
    }

    public function testNoReminderOnNonBusinessDay(): void
    {
        // Samedi 05/09 : plancher anti-spam — jamais de relance un jour non ouvré.
        self::assertSame([], $this->plan('2026-09-05 09:00:00'));
    }

    public function testOptedOutUserIsExcluded(): void
    {
        $this->preferences->save(new ReminderPreference($this->tenant, self::USER, true, $this->at('2026-08-01 09:00:00')));

        self::assertSame([], $this->plan('2026-09-02 09:00:00'));
    }

    public function testGloballyDisabledTenantEmitsNothing(): void
    {
        $rule = ReminderRule::default($this->tenant);
        $rule->deactivate();
        $this->rules = new InMemoryReminderRuleRepository();
        $this->rules->save($rule);

        self::assertSame([], $this->plan('2026-09-02 09:00:00'));
    }

    public function testNoRuleConfiguredEmitsNothing(): void
    {
        $this->rules = new InMemoryReminderRuleRepository();

        self::assertSame([], $this->plan('2026-09-02 09:00:00'));
    }

    public function testFrequencyFloorBlocksReminderTooSoon(): void
    {
        // Relance envoyée le 02/09 ; fréquence 3 j → jeudi 03/09 encore trop tôt.
        $this->logs->save($this->log(1, false, '2026-09-02 08:00:00'));

        self::assertNull($this->decisionFor($this->plan('2026-09-03 09:00:00'), self::WEEK));
    }

    public function testSecondReminderAfterFrequencyElapsed(): void
    {
        $this->logs->save($this->log(1, false, '2026-09-02 08:00:00'));

        // Lundi 07/09 : 5 jours écoulés (≥ 3) → deuxième relance, pas encore d'escalade.
        $decision = $this->decisionFor($this->plan('2026-09-07 09:00:00'), self::WEEK);

        self::assertInstanceOf(ReminderDecision::class, $decision);
        self::assertSame(2, $decision->sequence);
        self::assertFalse($decision->escalated);
    }

    public function testThirdReminderEscalatesToManager(): void
    {
        $this->logs->save($this->log(2, false, '2026-09-07 08:00:00'));

        // Jeudi 10/09 : 3 jours écoulés → troisième relance, escalade N+1 activée.
        $decision = $this->decisionFor($this->plan('2026-09-10 09:00:00'), self::WEEK);

        self::assertInstanceOf(ReminderDecision::class, $decision);
        self::assertSame(3, $decision->sequence);
        self::assertTrue($decision->escalated);
    }

    public function testEscalationDisabledKeepsThirdReminderPlain(): void
    {
        $rule = new ReminderRule($this->tenant, 1, 3, ReminderChannel::IN_APP, false, true);
        $this->rules = new InMemoryReminderRuleRepository();
        $this->rules->save($rule);
        $this->logs->save($this->log(2, false, '2026-09-07 08:00:00'));

        $decision = $this->decisionFor($this->plan('2026-09-10 09:00:00'), self::WEEK);

        self::assertInstanceOf(ReminderDecision::class, $decision);
        self::assertSame(3, $decision->sequence);
        self::assertFalse($decision->escalated);
    }

    /**
     * @return list<ReminderDecision>
     */
    private function plan(string $now): array
    {
        $engine = new ScheduleReminders(
            $this->rules,
            $this->preferences,
            $this->logs,
            $this->users,
            new CompletenessGrid($this->entries, $this->absences),
        );

        return $engine->plan($this->tenant, $this->at($now));
    }

    /**
     * @param list<ReminderDecision> $decisions
     */
    private function decisionFor(array $decisions, string $mondayIso): ?ReminderDecision
    {
        foreach ($decisions as $decision) {
            if ($decision->weekStart->format('Y-m-d') === $mondayIso) {
                return $decision;
            }
        }

        return null;
    }

    private function fillWeek(string $mondayIso): void
    {
        $monday = $this->date($mondayIso);
        for ($i = 0; $i < 5; ++$i) {
            $this->entries->save(new TimeEntry($this->tenant, self::USER, self::PROJECT, $monday->modify(sprintf('+%d days', $i)), 420));
        }
    }

    private function log(int $sequence, bool $escalated, string $sentAt): ReminderLog
    {
        return new ReminderLog($this->tenant, self::USER, $this->date(self::WEEK), ReminderChannel::IN_APP, $sequence, $escalated, $this->at($sentAt));
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
