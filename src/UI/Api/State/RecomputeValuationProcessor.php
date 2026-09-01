<?php

declare(strict_types=1);

namespace App\UI\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Valuation\RecomputeValuation;
use App\Domain\Valuation\ValuationException;
use App\UI\Api\Resource\RecomputeValuationResource;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Déclenche le recalcul de valorisation d'une période via le cas d'usage (US-060, CA-5).
 *
 * La période est lue depuis le paramètre de requête `?period=YYYY-MM` (spec CA-5), avec repli
 * sur le corps. Habilitation, verrou de clôture (423) et validation portés par
 * {@see RecomputeValuation} et les listeners d'exception.
 *
 * @implements ProcessorInterface<RecomputeValuationResource, RecomputeValuationResource>
 */
final readonly class RecomputeValuationProcessor implements ProcessorInterface
{
    public function __construct(
        private RecomputeValuation $recompute,
        private CurrentUser $currentUser,
        private RequestStack $requestStack,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): RecomputeValuationResource
    {
        $user = $this->currentUser->require();

        $period = $this->requestStack->getCurrentRequest()?->query->getString('period') ?? '';
        if ('' === $period && null !== $data->period) {
            $period = $data->period;
        }
        if ('' === $period) {
            throw new ValuationException('Le paramètre « period » (YYYY-MM) est obligatoire.');
        }

        $count = $this->recompute->forPeriod($user->tenantId(), $user, $period);

        return new RecomputeValuationResource(period: $period, recomputed: $count);
    }
}
