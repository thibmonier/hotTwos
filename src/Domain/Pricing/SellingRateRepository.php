<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

use App\Domain\Tenant\TenantId;

interface SellingRateRepository
{
    public function save(SellingRate $rate): void;

    /**
     * Surcharges d'un profil pour un scope/ref donné (client ou projet), triées par date d'effet.
     *
     * @return list<SellingRate>
     */
    public function findForScope(TenantId $tenant, string $profileId, RateScope $scope, string $scopeRefId): array;
}
