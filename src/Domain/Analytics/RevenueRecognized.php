<?php

declare(strict_types=1);

namespace App\Domain\Analytics;

use App\Domain\Tenant\TenantId;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Événement de sonde (US-005) : un chiffre d'affaires reconnu sur une période et un
 * projet. Représente le fait métier « revenue » alimentant `fact_project_revenue`.
 *
 * Montant en centimes entiers (jamais de flottant sur une donnée financière — INV-2).
 * La période est un mois calendaire au format `YYYY-MM`.
 */
final readonly class RevenueRecognized implements DomainEvent
{
    private const string PERIOD_FORMAT = '/^\d{4}-(0[1-9]|1[0-2])$/';

    /**
     * @param non-empty-string $projectRef
     * @param non-empty-string $period     mois au format YYYY-MM
     */
    public function __construct(
        private TenantId $tenantId,
        private string $period,
        private string $projectRef,
        private int $amountCents,
        private DateTimeImmutable $occurredAt,
    ) {
        if (1 !== preg_match(self::PERIOD_FORMAT, $period)) {
            throw new InvalidArgumentException(sprintf('Période invalide « %s » (attendu YYYY-MM).', $period));
        }
    }

    public function tenantId(): TenantId
    {
        return $this->tenantId;
    }

    public function name(): string
    {
        return 'revenue_recognized';
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function payload(): array
    {
        return [
            'period' => $this->period,
            'project_ref' => $this->projectRef,
            'amount_cents' => $this->amountCents,
        ];
    }
}
