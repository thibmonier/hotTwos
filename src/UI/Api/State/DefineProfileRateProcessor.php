<?php

declare(strict_types=1);

namespace App\UI\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Pricing\DefineProfileRate;
use App\Domain\Pricing\PricingException;
use App\Domain\Shared\EffectivePeriod;
use App\UI\Api\Resource\ProfileRateResource;
use DateTimeImmutable;

/**
 * Définit une entrée tarifaire via le cas d'usage (US-011). Convertit les dates d'effet (`Y-m-d`)
 * en {@see EffectivePeriod} ; habilitation et règles (chevauchement, ≤ 0, rétroactif) portées par
 * DefineProfileRate (403/422 via listeners).
 *
 * @implements ProcessorInterface<ProfileRateResource, ProfileRateResource>
 */
final readonly class DefineProfileRateProcessor implements ProcessorInterface
{
    public function __construct(
        private DefineProfileRate $defineProfileRate,
        private CurrentUser $currentUser,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ProfileRateResource
    {
        $user = $this->currentUser->require();
        $period = $this->buildPeriod($data->effectiveFrom, $data->effectiveTo);

        $id = $this->defineProfileRate->define(
            $user->tenantId(),
            $user,
            $data->profileId,
            $period,
            $data->costPriceCents,
            $data->sellingPriceCents,
            $data->confirmRetroactive,
        );

        return new ProfileRateResource(
            id: $id,
            profileId: $data->profileId,
            effectiveFrom: $period->from()->format('Y-m-d'),
            effectiveTo: $period->to()?->format('Y-m-d'),
            costPriceCents: $data->costPriceCents,
            sellingPriceCents: $data->sellingPriceCents,
        );
    }

    private function buildPeriod(?string $from, ?string $to): EffectivePeriod
    {
        if (null === $from || '' === $from) {
            throw new PricingException('La date d\'effet (effectiveFrom) est obligatoire.');
        }

        $start = $this->parseDate($from);

        return null === $to || '' === $to
            ? EffectivePeriod::since($start)
            : EffectivePeriod::between($start, $this->parseDate($to));
    }

    private function parseDate(string $value): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (false === $date) {
            throw new PricingException(sprintf('Date invalide (attendu Y-m-d) : %s.', $value));
        }

        return $date;
    }
}
