<?php

declare(strict_types=1);

namespace App\Application\Onboarding;

use App\Application\Authorization\InitializeDefaultRoles;
use App\Domain\Calendar\Holiday;
use App\Domain\Calendar\HolidayRepository;
use App\Domain\Client\Client;
use App\Domain\Client\ClientRepository;
use App\Domain\Currency\ReferenceCurrency;
use App\Domain\Currency\ReferenceCurrencyRepository;
use App\Domain\Pricing\CalculationMode;
use App\Domain\Pricing\Profile;
use App\Domain\Pricing\ProfileRepository;
use App\Domain\Skill\SkillLevelScale;
use App\Domain\Skill\SkillLevelScaleRepository;
use App\Domain\Tenant\TenantId;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;

/**
 * US-019 (EF-REF-29) — provisionne les valeurs par défaut d'un tenant pour un usage immédiat
 * (RG-REF-3). **Idempotent** : chaque défaut n'est créé que s'il est absent, l'opération est rejouable.
 */
final readonly class InitializeTenantDefaults
{
    private const string DEFAULT_CURRENCY = 'EUR';
    private const string DEFAULT_PROFILE = 'Consultant';
    private const string DEFAULT_CLIENT = 'Client interne';

    public function __construct(
        private InitializeDefaultRoles $roles,
        private ReferenceCurrencyRepository $currencies,
        private SkillLevelScaleRepository $scales,
        private ProfileRepository $profiles,
        private ClientRepository $clients,
        private HolidayRepository $holidays,
        private ClockInterface $clock,
    ) {
    }

    public function forTenant(TenantId $tenant): void
    {
        $this->roles->forTenant($tenant);

        if (!$this->currencies->findForTenant($tenant) instanceof ReferenceCurrency) {
            $this->currencies->save(new ReferenceCurrency($tenant, self::DEFAULT_CURRENCY));
        }

        if (!$this->scales->findForTenant($tenant) instanceof SkillLevelScale) {
            $this->scales->save(new SkillLevelScale($tenant));
        }

        if ([] === $this->profiles->findByTenant($tenant)) {
            $this->profiles->save(new Profile($tenant, self::DEFAULT_PROFILE, CalculationMode::LOADED));
        }

        if ([] === $this->clients->findAllByTenant($tenant)) {
            $this->clients->save(new Client($tenant, self::DEFAULT_CLIENT));
        }

        if ([] === $this->holidays->findForTenant($tenant)) {
            foreach ($this->defaultHolidays() as $date => $label) {
                $this->holidays->save(new Holiday($tenant, new DateTimeImmutable($date), $label));
            }
        }
    }

    /**
     * Jours fériés français à date fixe de l'année civile en cours (les fériés mobiles — Pâques — sont
     * hors périmètre par défaut, ajoutables manuellement).
     *
     * @return array<string, string>
     */
    private function defaultHolidays(): array
    {
        $year = (int) $this->clock->now()->format('Y');

        return [
            sprintf('%d-01-01', $year) => 'Jour de l\'an',
            sprintf('%d-05-01', $year) => 'Fête du travail',
            sprintf('%d-05-08', $year) => 'Victoire 1945',
            sprintf('%d-07-14', $year) => 'Fête nationale',
            sprintf('%d-08-15', $year) => 'Assomption',
            sprintf('%d-11-01', $year) => 'Toussaint',
            sprintf('%d-11-11', $year) => 'Armistice 1918',
            sprintf('%d-12-25', $year) => 'Noël',
        ];
    }
}
