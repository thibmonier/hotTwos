<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

use App\Domain\Tenant\TenantId;
use DateTimeImmutable;

/**
 * Moteur de résolution tarifaire (US-011, ARC-6) — **service unique** consommé par la
 * valorisation (US-060).
 *
 * Résout le tarif **en vigueur à une date** (`from <= date < to`), de façon déterministe :
 * les entrées d'un profil ne se chevauchent pas (garanti par DefineProfileRate), la première
 * dont la période contient la date est donc l'unique applicable. Absence de tarif à la date →
 * {@see NoEffectiveRateException} (consommée par US-060 CA-4).
 */
final readonly class RateResolver
{
    public function __construct(private ProfileRateRepository $rates)
    {
    }

    public function resolveAt(TenantId $tenant, string $profileId, DateTimeImmutable $date): ProfileRate
    {
        foreach ($this->rates->findForProfile($tenant, $profileId) as $rate) {
            if ($rate->period()->contains($date)) {
                return $rate;
            }
        }

        throw new NoEffectiveRateException(sprintf('Aucun tarif en vigueur pour le profil %s à la date %s.', $profileId, $date->format('Y-m-d')));
    }
}
