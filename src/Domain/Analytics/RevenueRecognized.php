<?php

declare(strict_types=1);

namespace App\Domain\Analytics;

use App\Domain\Tenant\TenantId;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Chiffre d'affaires reconnu sur une période et un projet — fait métier « revenue »
 * alimentant `fact_project_revenue`. Introduit comme sonde (US-005), il porte désormais
 * le CA réel issu de la valorisation des temps validés (US-060, T-060-04).
 *
 * Montant en centimes entiers (jamais de flottant sur une donnée financière — INV-2).
 * La période est un mois calendaire au format `YYYY-MM`.
 *
 * `sourceTimeEntryId` relie la reconnaissance à l'imputation qui l'a produite : la
 * projection retient alors la **dernière** reconnaissance par imputation (une re-valorisation
 * remplace la précédente au lieu de s'y ajouter — pas de double comptage, CA-4). Les
 * reconnaissances sans source (sonde) restent additionnées indépendamment.
 */
final readonly class RevenueRecognized implements DomainEvent
{
    private const string PERIOD_FORMAT = '/^\d{4}-(0[1-9]|1[0-2])$/';

    /**
     * @param non-empty-string $period            mois au format YYYY-MM
     * @param string|null      $sourceTimeEntryId imputation à l'origine du CA (US-060)
     */
    public function __construct(
        private TenantId $tenantId,
        private string $period,
        private string $projectRef,
        private int $amountCents,
        private DateTimeImmutable $occurredAt,
        private ?string $sourceTimeEntryId = null,
    ) {
        if (1 !== preg_match(self::PERIOD_FORMAT, $period)) {
            throw new InvalidArgumentException(sprintf('Période invalide « %s » (attendu YYYY-MM).', $period));
        }
        if ('' === trim($projectRef)) {
            throw new InvalidArgumentException('La référence projet d\'une reconnaissance de CA ne peut pas être vide.');
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
        $payload = [
            'period' => $this->period,
            'project_ref' => $this->projectRef,
            'amount_cents' => $this->amountCents,
        ];

        // Clé n'apparaît que pour le CA réel (US-060) : la charge utile de la sonde (US-005)
        // reste strictement inchangée, préservant l'immutabilité des flux déjà persistés.
        if (null !== $this->sourceTimeEntryId) {
            $payload['source_time_entry_id'] = $this->sourceTimeEntryId;
        }

        return $payload;
    }
}
