<?php

declare(strict_types=1);

namespace App\Domain\Budget;

use App\Domain\Project\ContractType;
use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

/**
 * US-079b (EF-PRJ-15) — seuil de dérive de charge paramétrable par **type de projet** : seuil d'alerte
 * (1er) et seuil d'escalade direction (2e). Généralise les constantes OBJ-2 (décision S12).
 */
#[ORM\Entity]
#[ORM\Table(name: 'charge_drift_threshold')]
#[ORM\UniqueConstraint(name: 'uniq_charge_drift_threshold', columns: ['tenant_id', 'contract_type'])]
class ChargeDriftThreshold implements TenantOwned
{
    private const float MAX_PERCENT = 100.0;

    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    public function __construct(
        TenantId $tenantId,
        #[ORM\Column(name: 'contract_type', length: 20, enumType: ContractType::class)]
        private ContractType $contractType,
        #[ORM\Column(name: 'alert_percent', type: 'float')]
        private float $alertPercent,
        #[ORM\Column(name: 'escalation_percent', type: 'float')]
        private float $escalationPercent,
    ) {
        $this->guard($alertPercent, $escalationPercent);
        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
    }

    public function reconfigure(float $alertPercent, float $escalationPercent): void
    {
        $this->guard($alertPercent, $escalationPercent);
        $this->alertPercent = $alertPercent;
        $this->escalationPercent = $escalationPercent;
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }

    public function contractType(): ContractType
    {
        return $this->contractType;
    }

    public function alertPercent(): float
    {
        return $this->alertPercent;
    }

    public function escalationPercent(): float
    {
        return $this->escalationPercent;
    }

    public function resolved(): ResolvedChargeDriftThreshold
    {
        return new ResolvedChargeDriftThreshold($this->alertPercent, $this->escalationPercent);
    }

    private function guard(float $alertPercent, float $escalationPercent): void
    {
        if ($alertPercent < 0.0 || $escalationPercent > self::MAX_PERCENT) {
            throw new InvalidArgumentException(sprintf('Les seuils de dérive de charge doivent être compris entre 0 et %d %%.', (int) self::MAX_PERCENT));
        }

        if ($escalationPercent < $alertPercent) {
            throw new InvalidArgumentException('Le seuil d\'escalade direction doit être supérieur ou égal au seuil d\'alerte.');
        }
    }
}
