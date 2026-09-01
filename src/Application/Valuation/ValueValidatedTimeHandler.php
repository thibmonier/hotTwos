<?php

declare(strict_types=1);

namespace App\Application\Valuation;

use App\Application\Timesheet\Message\TimeEntriesValidated;
use App\Domain\Analytics\EventStore;
use App\Domain\Analytics\RevenueRecognized;
use App\Domain\Pricing\NoEffectiveRateException;
use App\Domain\Pricing\ProfileAssignmentRepository;
use App\Domain\Pricing\RateResolver;
use App\Domain\Tenant\TenantId;
use App\Domain\Timesheet\TimeEntry;
use App\Domain\Timesheet\TimeEntryRepository;
use App\Domain\Valuation\TimeEntryValuation;
use App\Domain\Valuation\TimeEntryValuationRepository;
use App\Domain\Valuation\TimeValuationCalculator;
use App\Domain\Valuation\ValuationStatus;
use DateTimeImmutable;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Valorisation asynchrone des temps validés (US-060, T-060-02).
 *
 * À la consommation de {@see TimeEntriesValidated} (hors requête HTTP, ≤ 15 min) : pour chaque
 * imputation, résout le profil du collaborateur à la date de prestation, puis le tarif en vigueur
 * (RateResolver, ARC-6), calcule coût et revenu, et **fige** le snapshot (INV-2/INV-3). En
 * l'absence de profil ou de tarif à la date, la valorisation est marquée `missing_rate` (CA-4).
 *
 * Chaque valorisation figée `valued` produit un événement analytique {@see RevenueRecognized}
 * appended à l'`EventStore` (US-060, T-060-04) : le projecteur en dérive `fact_project_revenue`,
 * jamais écrit directement (ADR-9). Une imputation sans tarif (`missing_rate`) ne reconnaît
 * aucun CA — la valorisation reste partielle jusqu'à correction du tarif (CA-4).
 */
#[AsMessageHandler]
final readonly class ValueValidatedTimeHandler
{
    public function __construct(
        private TimeEntryRepository $entries,
        private ProfileAssignmentRepository $assignments,
        private RateResolver $rates,
        private TimeValuationCalculator $calculator,
        private TimeEntryValuationRepository $valuations,
        private EventStore $events,
    ) {
    }

    public function __invoke(TimeEntriesValidated $message): void
    {
        $tenant = $message->tenantId();
        $validatedAt = $message->validatedAt();

        foreach ($this->entries->findByIds($tenant, $message->timeEntryIds()) as $entry) {
            $valuation = $this->value($tenant, $entry, $validatedAt);
            $this->valuations->save($valuation);
            $this->recognizeRevenue($tenant, $entry, $valuation, $validatedAt);
        }
    }

    /**
     * Reconnaît le CA réel de l'imputation valorisée (temps validé × taux de vente figé)
     * sur le mois de la prestation. Idempotent à la re-valorisation via `sourceTimeEntryId`
     * (la reconnaissance précédente est remplacée, pas cumulée — voir {@see RevenueRecognized}).
     */
    private function recognizeRevenue(
        TenantId $tenant,
        TimeEntry $entry,
        TimeEntryValuation $valuation,
        DateTimeImmutable $occurredAt,
    ): void {
        if (ValuationStatus::VALUED !== $valuation->status()) {
            return;
        }

        $this->events->append(new RevenueRecognized(
            $tenant,
            $entry->workDate()->format('Y-m'),
            $entry->projectId(),
            $valuation->revenueCents(),
            $occurredAt,
            $entry->id(),
        ));
    }

    private function value(TenantId $tenant, TimeEntry $entry, DateTimeImmutable $validatedAt): TimeEntryValuation
    {
        $profileId = $this->resolveProfile($tenant, $entry->userId(), $entry->workDate());
        if (null === $profileId) {
            return TimeEntryValuation::missingRate($tenant, $entry->id(), $validatedAt);
        }

        try {
            $rate = $this->rates->resolveAt($tenant, $profileId, $entry->workDate());
        } catch (NoEffectiveRateException) {
            return TimeEntryValuation::missingRate($tenant, $entry->id(), $validatedAt);
        }

        return TimeEntryValuation::valued(
            $tenant,
            $entry->id(),
            $this->calculator->entryCents($rate->costPriceCents(), $entry->minutes()),
            $this->calculator->entryCents($rate->sellingPriceCents(), $entry->minutes()),
            $rate->costPriceCents(),
            $rate->sellingPriceCents(),
            $rate->period()->from(),
            $validatedAt,
        );
    }

    private function resolveProfile(TenantId $tenant, string $userId, DateTimeImmutable $workDate): ?string
    {
        foreach ($this->assignments->findForUser($tenant, $userId) as $assignment) {
            if ($assignment->period()->contains($workDate)) {
                return $assignment->profileId();
            }
        }

        return null;
    }
}
