<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Filter;

use App\Domain\Tenant\TenantOwned;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

/**
 * Première barrière d'isolation multi-tenant (ARC-33) : ajoute automatiquement
 * `tenant_id = :tenant_id` à toute requête portant sur une entité {@see TenantOwned}.
 *
 * Le paramètre `tenant_id` est positionné par requête ({@see TenantFilterConfigurator})
 * depuis le tenant courant (ARC-61). La seconde barrière est la RLS PostgreSQL (ARC-34).
 */
final class TenantFilter extends SQLFilter
{
    public const string NAME = 'tenant';
    public const string PARAMETER = 'tenant_id';

    public function addFilterConstraint(ClassMetadata $targetEntity, string $targetTableAlias): string
    {
        if (!$targetEntity->getReflectionClass()->implementsInterface(TenantOwned::class)) {
            return '';
        }

        return sprintf('%s.tenant_id = %s', $targetTableAlias, $this->getParameter(self::PARAMETER));
    }
}
