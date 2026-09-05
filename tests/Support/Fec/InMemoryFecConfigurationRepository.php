<?php

declare(strict_types=1);

namespace App\Tests\Support\Fec;

use App\Domain\Fec\FecConfiguration;
use App\Domain\Fec\FecConfigurationRepository;
use App\Domain\Tenant\TenantId;

final class InMemoryFecConfigurationRepository implements FecConfigurationRepository
{
    /** @var list<FecConfiguration> */
    public array $configs = [];

    public function findForTenant(TenantId $tenant): ?FecConfiguration
    {
        foreach ($this->configs as $config) {
            if ($config->tenantId()->equals($tenant)) {
                return $config;
            }
        }

        return null;
    }

    public function save(FecConfiguration $configuration): void
    {
        $this->configs = array_values(array_filter(
            $this->configs,
            static fn (FecConfiguration $c): bool => !$c->tenantId()->equals($configuration->tenantId()),
        ));
        $this->configs[] = $configuration;
    }
}
