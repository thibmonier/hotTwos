<?php

declare(strict_types=1);

namespace App\Domain\Analytics;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use InvalidArgumentException;

/**
 * Dimension « période » du schéma en étoile (US-005, CA-4). Porte le discriminant tenant
 * (double barrière : filtre ORM ARC-33 + RLS ARC-34).
 *
 * Écrite exclusivement par le projecteur analytique (ARC-111) lors de la reconstruction.
 */
#[ORM\Entity]
#[ORM\Table(name: 'dim_period')]
#[ORM\UniqueConstraint(name: 'uniq_dim_period_tenant', columns: ['tenant_id', 'period'])]
class DimPeriod implements TenantOwned
{
    private const string PERIOD_FORMAT = '/^\d{4}-(0[1-9]|1[0-2])$/';

    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    #[ORM\Column(length: 7)]
    private string $period;

    #[ORM\Column(type: 'integer')]
    private int $year;

    #[ORM\Column(type: 'integer')]
    private int $month;

    public function __construct(TenantId $tenantId, string $period)
    {
        if (1 !== preg_match(self::PERIOD_FORMAT, $period)) {
            throw new InvalidArgumentException(sprintf('Période invalide « %s » (attendu YYYY-MM).', $period));
        }

        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
        $this->period = $period;
        [$year, $month] = explode('-', $period);
        $this->year = (int) $year;
        $this->month = (int) $month;
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }

    public function period(): string
    {
        return $this->period;
    }
}
