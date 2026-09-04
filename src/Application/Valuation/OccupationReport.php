<?php

declare(strict_types=1);

namespace App\Application\Valuation;

use App\Domain\Absence\AbsenceRequest;
use App\Domain\Absence\AbsenceRequestRepository;
use App\Domain\Tenant\TenantId;
use App\Domain\Valuation\OccupationLine;
use App\Domain\Valuation\OccupationOverview;
use App\Domain\Valuation\TimeEntryValuationRepository;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;

/**
 * US-060 (T-060-03) — calcule le taux d'occupation par collaborateur : jours valorisés / capacité,
 * où la capacité = jours ouvrés (lun-ven) − absences validées (même logique que
 * {@see \App\Application\Completeness\CompletenessGrid}).
 *
 * **Mois de référence** : le mois de la prestation valorisée la plus récente (repli : mois courant).
 * Ce choix cadre l'indicateur sur les données réellement présentes (pas de biais de période partielle)
 * et le rend démontrable ; un sélecteur de période reste hors périmètre (YAGNI).
 */
final readonly class OccupationReport
{
    private const int WORKING_DAYS_PER_WEEK = 5;

    public function __construct(
        private TimeEntryValuationRepository $valuations,
        private AbsenceRequestRepository $absences,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @param list<string> $userIds
     */
    public function forTenant(TenantId $tenant, array $userIds): OccupationOverview
    {
        $reference = $this->valuations->latestValuedWorkDate($tenant) ?? $this->clock->now();
        $from = $reference->modify('first day of this month')->setTime(0, 0);
        $to = $from->modify('+1 month');

        $workingDays = $this->workingDaysBetween($from, $to);
        $valuedByUser = $this->valuations->valuedDayCountByUser($tenant, $from, $to);

        $lines = [];
        foreach ($userIds as $userId) {
            $valued = $valuedByUser[$userId] ?? 0;
            if (0 === $valued) {
                continue; // n'afficher que les collaborateurs ayant une activité valorisée sur le mois.
            }

            $capacity = max(0, $workingDays - $this->absenceDays($tenant, $userId, $from, $to));
            $lines[] = new OccupationLine($userId, $valued, $capacity);
        }

        usort($lines, static fn (OccupationLine $a, OccupationLine $b): int => $b->percent() <=> $a->percent());

        return new OccupationOverview($from->format('Y-m'), $lines);
    }

    private function workingDaysBetween(DateTimeImmutable $from, DateTimeImmutable $to): int
    {
        $count = 0;
        for ($day = $from; $day < $to; $day = $day->modify('+1 day')) {
            if ($this->isWeekday($day)) {
                ++$count;
            }
        }

        return $count;
    }

    private function absenceDays(TenantId $tenant, string $userId, DateTimeImmutable $from, DateTimeImmutable $to): int
    {
        $lastDay = $to->modify('-1 day');
        $absences = $this->absences->findValidatedOverlapping($tenant, $userId, $from, $lastDay);

        $count = 0;
        for ($day = $from; $day < $to; $day = $day->modify('+1 day')) {
            if ($this->isWeekday($day) && $this->isAbsent($absences, $day)) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * @param list<AbsenceRequest> $absences
     */
    private function isAbsent(array $absences, DateTimeImmutable $day): bool
    {
        return array_any($absences, static fn (AbsenceRequest $absence): bool => $absence->coversDay($day));
    }

    private function isWeekday(DateTimeImmutable $day): bool
    {
        return (int) $day->format('N') <= self::WORKING_DAYS_PER_WEEK;
    }
}
