<?php

declare(strict_types=1);

namespace App\Application\Reminder;

use App\Application\Completeness\CompletenessGrid;
use App\Domain\Completeness\CompletenessState;
use App\Domain\Completeness\WeekCompleteness;
use App\Domain\Reminder\ReminderChannel;
use App\Domain\Reminder\ReminderLog;
use App\Domain\Reminder\ReminderLogRepository;
use App\Domain\Reminder\ReminderNotifier;
use App\Domain\Reminder\ReminderPreferenceRepository;
use App\Domain\Reminder\ReminderRule;
use App\Domain\Reminder\ReminderRuleRepository;
use App\Domain\Tenant\TenantId;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;

/**
 * US-092b (CPL-04) — relance **manuelle immédiate** déclenchée par un manager depuis la grille de
 * complétude, pour une sélection de collaborateurs. Réutilise le canal existant (US-056) : journal
 * {@see ReminderLog} + {@see ReminderNotifier}, sans nouveau canal.
 *
 * Contrairement au moteur automatique {@see ScheduleReminders}, ignore les bornes temporelles (délai
 * initial, fréquence, plancher par jour) — l'envoi est explicite et intentionnel — mais respecte
 * l'opt-out individuel (INV : l'opt-out prime) et ne relance qu'un collaborateur réellement en retard
 * sur au moins une des 4 dernières semaines. L'habilitation `MANAGE_REMINDERS` est vérifiée par
 * l'appelant (adaptateur web).
 */
final readonly class SendManualReminders
{
    /** Fenêtre glissante des semaines surveillées (identique au moteur automatique). */
    private const int LOOKBACK_WEEKS = 4;

    public function __construct(
        private ReminderRuleRepository $rules,
        private ReminderPreferenceRepository $preferences,
        private CompletenessGrid $completeness,
        private ReminderLogRepository $logs,
        private ReminderNotifier $notifier,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @param list<string> $userIds collaborateurs sélectionnés
     *
     * @return int nombre de relances effectivement émises
     */
    public function send(TenantId $tenant, array $userIds): int
    {
        $targets = $this->eligibleTargets($tenant, $userIds);
        if ([] === $targets) {
            return 0;
        }

        $now = $this->clock->now();
        $channel = $this->channelFor($tenant);
        $lateWeeks = $this->oldestLateWeekByUser($tenant, $targets, $now);

        $sent = 0;
        foreach ($targets as $userId) {
            $week = $lateWeeks[$userId] ?? null;
            if (!$week instanceof WeekCompleteness) {
                continue; // collaborateur à jour : rien à relancer
            }
            $last = $this->logs->latestFor($tenant, $userId, $week->weekStart);
            $sequence = ($last instanceof ReminderLog ? $last->sequence() : 0) + 1;
            $log = new ReminderLog($tenant, $userId, $week->weekStart, $channel, $sequence, false, $now, 'relance manuelle');
            $this->logs->save($log);
            $this->notifier->send($log);
            ++$sent;
        }

        return $sent;
    }

    /**
     * @param list<string> $userIds
     *
     * @return list<string> collaborateurs sélectionnés, dédupliqués, hors opt-out
     */
    private function eligibleTargets(TenantId $tenant, array $userIds): array
    {
        $selected = array_values(array_unique(array_filter($userIds, static fn (string $id): bool => '' !== $id)));
        if ([] === $selected) {
            return [];
        }

        $optedOut = $this->preferences->findOptedOutUserIds($tenant);

        return array_values(array_filter($selected, static fn (string $id): bool => !in_array($id, $optedOut, true)));
    }

    private function channelFor(TenantId $tenant): ReminderChannel
    {
        $rule = $this->rules->findForTenant($tenant);

        return $rule instanceof ReminderRule ? $rule->channel() : ReminderChannel::IN_APP;
    }

    /**
     * @param list<string> $userIds
     *
     * @return array<string, WeekCompleteness> semaine en retard la plus ancienne par collaborateur
     */
    private function oldestLateWeekByUser(TenantId $tenant, array $userIds, DateTimeImmutable $now): array
    {
        $byUser = [];
        foreach ($this->completeness->build($tenant, $userIds, $now, self::LOOKBACK_WEEKS) as $week) {
            if (!$this->isLate($week->state)) {
                continue;
            }
            $current = $byUser[$week->userId] ?? null;
            if (!$current instanceof WeekCompleteness || $week->weekStart < $current->weekStart) {
                $byUser[$week->userId] = $week;
            }
        }

        return $byUser;
    }

    private function isLate(CompletenessState $state): bool
    {
        return CompletenessState::PARTIAL === $state || CompletenessState::EMPTY_LATE === $state;
    }
}
