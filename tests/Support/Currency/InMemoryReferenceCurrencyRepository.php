<?php

declare(strict_types=1);

namespace App\Tests\Support\Currency;

use App\Domain\Currency\ReferenceCurrency;
use App\Domain\Currency\ReferenceCurrencyRepository;
use App\Domain\Tenant\TenantId;

final class InMemoryReferenceCurrencyRepository implements ReferenceCurrencyRepository
{
    /** @var list<ReferenceCurrency> */
    public array $references = [];

    public function findForTenant(TenantId $tenant): ?ReferenceCurrency
    {
        foreach ($this->references as $ref) {
            if ($ref->tenantId()->equals($tenant)) {
                return $ref;
            }
        }

        return null;
    }

    public function save(ReferenceCurrency $reference): void
    {
        foreach ($this->references as $existing) {
            if ($existing === $reference) {
                return;
            }
        }
        $this->references[] = $reference;
    }
}
