<?php

declare(strict_types=1);

namespace App\UI\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\Absence\AbsenceBalance;
use App\Domain\Absence\AbsenceException;
use App\Domain\Absence\AbsenceRequestRepository;
use App\Domain\Absence\AbsenceStatus;
use App\Domain\Calendar\ClosurePeriodRepository;
use App\Domain\Calendar\WorkingDaysCalculator;
use App\Domain\Tenant\TenantId;
use App\UI\Api\Resource\AbsenceImpactResource;
use DateTimeImmutable;

/**
 * US-091b (CA-2/CA-3) — impact d'une demande sur la période `?from=&to=` : jours **ouvrés** concernés
 * (hors week-ends/fériés/fermetures via {@see WorkingDaysCalculator}), solde projeté après la demande,
 * et détection de conflit (fermeture d'entreprise ou absence déjà posée). Dates invalides → 422.
 *
 * @implements ProviderInterface<AbsenceImpactResource>
 */
final readonly class AbsenceImpactProvider implements ProviderInterface
{
    public function __construct(
        private WorkingDaysCalculator $calculator,
        private AbsenceBalance $balance,
        private ClosurePeriodRepository $closures,
        private AbsenceRequestRepository $requests,
        private CurrentUser $currentUser,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AbsenceImpactResource
    {
        $user = $this->currentUser->require();
        $filters = $context['filters'] ?? [];

        $from = $this->parseDate(is_array($filters) && is_string($filters['from'] ?? null) ? $filters['from'] : null);
        $to = $this->parseDate(is_array($filters) && is_string($filters['to'] ?? null) ? $filters['to'] : null);
        if ($to < $from) {
            throw new AbsenceException('La date de fin doit être postérieure ou égale à la date de début.');
        }

        // Intervalle semi-ouvert [from, to+1[ : la borne haute est exclue par le calculateur.
        $businessDays = $this->calculator->workingDaysForUser($user->tenantId(), $user->id(), $from, $to->modify('+1 day'));
        $projected = $this->balance->for($user->tenantId(), $user->id())->projectedBalance() - $businessDays;
        $conflict = $this->detectConflict($user->tenantId(), $user->id(), $from, $to);

        return new AbsenceImpactResource(
            businessDays: $businessDays,
            projectedBalance: $projected,
            hasConflict: null !== $conflict,
            conflictMessage: $conflict,
        );
    }

    private function detectConflict(TenantId $tenant, string $userId, DateTimeImmutable $from, DateTimeImmutable $to): ?string
    {
        foreach ($this->closures->findForTenant($tenant) as $closure) {
            if ($closure->startDate() <= $to && $closure->endDate() >= $from) {
                return sprintf('la période chevauche une fermeture d\'entreprise (%s).', $closure->label());
            }
        }

        foreach ($this->requests->findForUser($tenant, $userId) as $request) {
            if (AbsenceStatus::REJECTED === $request->status()) {
                continue;
            }
            if ($request->startDate() <= $to && $request->endDate() >= $from) {
                return sprintf('la période chevauche une demande déjà posée (du %s au %s).', $request->startDate()->format('d/m/Y'), $request->endDate()->format('d/m/Y'));
            }
        }

        return null;
    }

    private function parseDate(?string $value): DateTimeImmutable
    {
        if (null === $value || '' === $value) {
            throw new AbsenceException('Les dates de début et de fin (Y-m-d) sont obligatoires.');
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();
        if (false === $date || (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw new AbsenceException(sprintf('Date invalide (attendu Y-m-d) : %s.', $value));
        }

        return $date;
    }
}
