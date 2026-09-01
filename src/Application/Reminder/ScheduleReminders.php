<?php

declare(strict_types=1);

namespace App\Application\Reminder;

use App\Application\Completeness\CompletenessGrid;
use App\Domain\Completeness\CompletenessState;
use App\Domain\Completeness\WeekCompleteness;
use App\Domain\Reminder\ReminderLog;
use App\Domain\Reminder\ReminderLogRepository;
use App\Domain\Reminder\ReminderPreferenceRepository;
use App\Domain\Reminder\ReminderRule;
use App\Domain\Reminder\ReminderRuleRepository;
use App\Domain\Tenant\TenantId;
use App\Domain\User\UserRepository;
use DateTimeImmutable;

/**
 * Moteur de planification des relances de retard de saisie (US-056, EF-TMP-21). **Pur et
 * déterministe** : ne produit aucun effet de bord, l'horloge est fournie par l'appelant, ce qui rend
 * la borne de fréquence et l'escalade testables hors temps réel. L'émission effective (journal +
 * notification) relève du handler {@see SendDueRemindersHandler}.
 *
 * Règles appliquées : arrêt automatique à la soumission (CA-3, une semaine soumise n'est jamais en
 * retard), respect de l'opt-out individuel (CA-2) et de la désactivation globale du tenant (CA-5),
 * délai initial paramétrable après l'échéance J+2 (US-058), puis fréquence paramétrable **bornée par
 * un plancher anti-spam hardcodé** — au plus une relance par jour ouvré (CA-4) — et escalade vers le
 * N+1 à partir de la troisième relance si activée.
 */
final readonly class ScheduleReminders
{
    /** Fenêtre glissante des semaines surveillées : pas de rattrapage rétroactif au-delà. */
    private const int LOOKBACK_WEEKS = 4;
    /** Rang à partir duquel une relance escalade vers le N+1 (si l'escalade est activée). */
    private const int ESCALATION_THRESHOLD = 3;
    /** Plancher anti-spam non paramétrable : au moins un jour ouvré entre deux relances (CA-4). */
    private const int MIN_FREQUENCY_DAYS = 1;

    public function __construct(
        private ReminderRuleRepository $rules,
        private ReminderPreferenceRepository $preferences,
        private ReminderLogRepository $logs,
        private UserRepository $users,
        private CompletenessGrid $completeness,
    ) {
    }

    /**
     * Relances dues à l'instant `$now` pour ce tenant. Liste vide si le tenant a désactivé les
     * relances, si `$now` n'est pas un jour ouvré, ou si aucune semaine n'est en retard.
     *
     * @return list<ReminderDecision>
     */
    public function plan(TenantId $tenant, DateTimeImmutable $now): array
    {
        $rule = $this->rules->findForTenant($tenant);
        if (!$rule instanceof ReminderRule || !$rule->isActive()) {
            return [];
        }
        // Plancher anti-spam : aucune relance émise un jour non ouvré, quelle que soit la config.
        if (!$this->isBusinessDay($now)) {
            return [];
        }

        $userIds = $this->targetUserIds($tenant);
        if ([] === $userIds) {
            return [];
        }

        /** @var array<string, list<ReminderDecision>> $candidatesByUser */
        $candidatesByUser = [];
        foreach ($this->completeness->build($tenant, $userIds, $now, self::LOOKBACK_WEEKS) as $week) {
            $decision = $this->decide($tenant, $rule, $week, $now);
            if ($decision instanceof ReminderDecision) {
                $candidatesByUser[$week->userId][] = $decision;
            }
        }

        return $this->capOnePerUserPerDay($tenant, $candidatesByUser, $now);
    }

    /**
     * Plancher anti-spam par jour ouvré (CA-4) : au plus **une** relance par collaborateur et par
     * jour. Un collaborateur déjà relancé aujourd'hui est ignoré ; sinon on ne retient que la dette
     * la plus ancienne (semaine la plus lointaine), la plus urgente à rattraper.
     *
     * @param array<string, list<ReminderDecision>> $candidatesByUser
     *
     * @return list<ReminderDecision>
     */
    private function capOnePerUserPerDay(TenantId $tenant, array $candidatesByUser, DateTimeImmutable $now): array
    {
        $decisions = [];
        foreach ($candidatesByUser as $userId => $candidates) {
            if ($this->logs->sentOnDay($tenant, $userId, $now)) {
                continue;
            }
            usort($candidates, static fn (ReminderDecision $a, ReminderDecision $b): int => $a->weekStart <=> $b->weekStart);
            $decisions[] = $candidates[0];
        }

        return $decisions;
    }

    private function decide(TenantId $tenant, ReminderRule $rule, WeekCompleteness $week, DateTimeImmutable $now): ?ReminderDecision
    {
        if (!$this->isLate($week->state)) {
            return null;
        }

        $last = $this->logs->latestFor($tenant, $week->userId, $week->weekStart);
        if ($last instanceof ReminderLog) {
            if (!$this->intervalElapsed($last->sentAt(), $now, $rule->frequencyDays())) {
                return null;
            }
            $sequence = $last->sequence() + 1;
        } else {
            if ($now < $this->firstReminderAt($week->weekStart, $rule->initialDelayDays())) {
                return null;
            }
            $sequence = 1;
        }

        $escalated = $rule->escalationEnabled() && $sequence >= self::ESCALATION_THRESHOLD;

        return new ReminderDecision($week->userId, $week->weekStart, $rule->channel(), $sequence, $escalated);
    }

    /**
     * Collaborateurs du tenant hors opt-out (CA-2). L'opt-out prime : un collaborateur qui s'est
     * désinscrit n'est jamais relancé, même si l'administrateur réactive globalement les relances.
     *
     * @return list<string>
     */
    private function targetUserIds(TenantId $tenant): array
    {
        $optedOut = $this->preferences->findOptedOutUserIds($tenant);

        return array_values(array_filter(
            $this->users->findIdsByTenant($tenant),
            static fn (string $id): bool => !in_array($id, $optedOut, true),
        ));
    }

    private function isLate(CompletenessState $state): bool
    {
        return CompletenessState::PARTIAL === $state || CompletenessState::EMPTY_LATE === $state;
    }

    /** Éligibilité de la **première** relance : échéance de complétude + délai initial paramétré. */
    private function firstReminderAt(DateTimeImmutable $weekStart, int $initialDelayDays): DateTimeImmutable
    {
        return CompletenessGrid::deadline($weekStart)->modify(sprintf('+%d days', $initialDelayDays));
    }

    private function intervalElapsed(DateTimeImmutable $lastSentAt, DateTimeImmutable $now, int $frequencyDays): bool
    {
        $interval = max(self::MIN_FREQUENCY_DAYS, $frequencyDays);
        $daysElapsed = (int) $lastSentAt->setTime(0, 0)->diff($now->setTime(0, 0))->days;

        return $daysElapsed >= $interval;
    }

    private function isBusinessDay(DateTimeImmutable $day): bool
    {
        return (int) $day->format('N') <= 5;
    }
}
