<?php

declare(strict_types=1);

namespace App\Application\Completeness;

use App\Domain\Absence\AbsenceRequest;
use App\Domain\Absence\AbsenceRequestRepository;
use App\Domain\Completeness\CompletenessState;
use App\Domain\Completeness\WeekCompleteness;
use App\Domain\Tenant\TenantId;
use App\Domain\Timesheet\TimeEntryRepository;
use DateTimeImmutable;

/**
 * Calcul de la grille de complétude de saisie (US-058, EF-TMP-24, OBJ-1).
 *
 * Pour chaque (collaborateur, semaine glissante), l'état dérive du taux de jours ouvrés saisis vs
 * attendus (Lun-Ven **moins** les jours d'absence validée), avec un délai J+2 : semaine soumise
 * (100 %), partielle, vide en retard (J+2 dépassé), ou en cours (délai non atteint).
 */
final readonly class CompletenessGrid
{
    private const int WORKING_DAYS = 5;
    /** Délai indicatif « J+2 ouvré » après la fin de semaine (raffinement jours fériés ultérieur). */
    public const int DEADLINE_OFFSET_DAYS = 8;

    public function __construct(
        private TimeEntryRepository $entries,
        private AbsenceRequestRepository $absences,
    ) {
    }

    /**
     * @param list<string> $userIds
     *
     * @return list<WeekCompleteness>
     */
    public function build(TenantId $tenant, array $userIds, DateTimeImmutable $now, int $weeks): array
    {
        $mondays = $this->recentMondays($now, $weeks);

        $grid = [];
        foreach ($userIds as $userId) {
            foreach ($mondays as $monday) {
                $grid[] = $this->week($tenant, $userId, $monday, $now);
            }
        }

        return $grid;
    }

    private function week(TenantId $tenant, string $userId, DateTimeImmutable $monday, DateTimeImmutable $now): WeekCompleteness
    {
        $friday = $monday->modify('+4 days');
        $absences = $this->absences->findValidatedOverlapping($tenant, $userId, $monday, $friday);

        $absentDays = 0;
        for ($day = $monday; $day <= $friday; $day = $day->modify('+1 day')) {
            if ($this->isAbsent($absences, $day)) {
                ++$absentDays;
            }
        }
        $expected = max(0, self::WORKING_DAYS - $absentDays);

        $filledDates = [];
        foreach ($this->entries->findForUserInRange($tenant, $userId, $monday, $friday) as $entry) {
            $filledDates[$entry->workDate()->format('Y-m-d')] = true;
        }
        $filled = count($filledDates);

        return new WeekCompleteness($userId, $monday, $expected, $filled, $this->state($expected, $filled, $monday, $now));
    }

    private function state(int $expected, int $filled, DateTimeImmutable $monday, DateTimeImmutable $now): CompletenessState
    {
        if (0 === $expected || $filled >= $expected) {
            return CompletenessState::SUBMITTED;
        }
        if ($now < self::deadline($monday)) {
            return CompletenessState::IN_PROGRESS;
        }

        return 0 === $filled ? CompletenessState::EMPTY_LATE : CompletenessState::PARTIAL;
    }

    /**
     * Échéance de complétude (« J+2 ouvré ») d'une semaine : instant à partir duquel une semaine
     * incomplète bascule en retard. Source unique du délai, réutilisée par le moteur de relances
     * (US-056) qui ajoute son propre délai initial par-dessus.
     */
    public static function deadline(DateTimeImmutable $weekStart): DateTimeImmutable
    {
        return $weekStart->modify(sprintf('+%d days', self::DEADLINE_OFFSET_DAYS));
    }

    /**
     * @param list<AbsenceRequest> $absences
     */
    private function isAbsent(array $absences, DateTimeImmutable $day): bool
    {
        return array_any($absences, fn (AbsenceRequest $absence): bool => $absence->coversDay($day));
    }

    /**
     * @return list<DateTimeImmutable> les `$weeks` derniers lundis, du plus ancien au plus récent
     */
    private function recentMondays(DateTimeImmutable $now, int $weeks): array
    {
        $thisMonday = $now->modify('monday this week')->setTime(0, 0);
        $mondays = [];
        for ($i = $weeks - 1; $i >= 0; --$i) {
            $mondays[] = $thisMonday->modify(sprintf('-%d weeks', $i));
        }

        return $mondays;
    }
}
