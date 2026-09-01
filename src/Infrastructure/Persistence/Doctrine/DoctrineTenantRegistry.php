<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Tenant\Tenant;
use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantRegistry;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Implémentation Doctrine du {@see TenantRegistry} (US-056, DIP). Lecture de la table registre
 * `tenant` (non cloisonnée) — usage système uniquement (cron).
 */
final readonly class DoctrineTenantRegistry implements TenantRegistry
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function allIds(): array
    {
        /** @var list<Tenant> $tenants */
        $tenants = $this->entityManager->createQuery('SELECT t FROM '.Tenant::class.' t')->getResult();

        return array_map(static fn (Tenant $tenant): TenantId => $tenant->id(), $tenants);
    }
}
