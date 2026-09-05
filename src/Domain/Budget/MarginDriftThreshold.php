<?php

declare(strict_types=1);

namespace App\Domain\Budget;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use InvalidArgumentException;

/**
 * Seuil de dérive du taux de marge paramétrable par tenant (US-018, override d'US-072).
 *
 * Une valeur par tenant, bornée. Sert de source au {@see \App\Infrastructure\Budget\TenantMarginDriftThresholdProvider}
 * qui remplace le défaut applicatif. Exprimé en **points de pourcentage** entiers (garde-fou 0..100).
 */
#[ORM\Entity]
#[ORM\Table(name: 'margin_drift_threshold')]
#[ORM\UniqueConstraint(name: 'uniq_margin_drift_threshold_tenant', columns: ['tenant_id'])]
class MarginDriftThreshold implements TenantOwned
{
    /** Borne haute défendable pour un seuil de dérive exprimé en points. */
    private const int MAX_POINTS = 100;

    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    public function __construct(
        TenantId $tenantId,
        #[ORM\Column(name: 'points', type: 'smallint')]
        private int $points,
    ) {
        $this->guard($points);
        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
    }

    public function reconfigure(int $points): void
    {
        $this->guard($points);
        $this->points = $points;
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }

    public function points(): int
    {
        return $this->points;
    }

    private function guard(int $points): void
    {
        if ($points < 0 || $points > self::MAX_POINTS) {
            throw new InvalidArgumentException(sprintf('Le seuil de dérive doit être compris entre 0 et %d points.', self::MAX_POINTS));
        }
    }
}
