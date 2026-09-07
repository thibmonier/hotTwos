<?php

declare(strict_types=1);

namespace App\Domain\Calendar;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

/**
 * US-022 (EF-REF-9) — période de fermeture entreprise (plage de jours). Exclue des jours ouvrés par
 * {@see WorkingDaysCalculator} pour l'ensemble du tenant, au même titre que les jours fériés.
 */
#[ORM\Entity]
#[ORM\Table(name: 'closure_period')]
#[ORM\Index(name: 'idx_closure_period_tenant', columns: ['tenant_id', 'start_date'])]
class ClosurePeriod implements TenantOwned
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    public function __construct(
        TenantId $tenantId,
        #[ORM\Column(name: 'start_date', type: 'date_immutable')]
        private DateTimeImmutable $startDate,
        #[ORM\Column(name: 'end_date', type: 'date_immutable')]
        private DateTimeImmutable $endDate,
        #[ORM\Column(name: 'label', length: 255)]
        private string $label,
    ) {
        $label = trim($label);
        if ('' === $label) {
            throw new InvalidArgumentException('Le libellé de la fermeture est obligatoire.');
        }
        $startDate = $startDate->setTime(0, 0);
        $endDate = $endDate->setTime(0, 0);
        if ($endDate < $startDate) {
            throw new InvalidArgumentException('La date de fin doit être postérieure ou égale à la date de début.');
        }
        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->label = $label;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }

    public function startDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function endDate(): DateTimeImmutable
    {
        return $this->endDate;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function covers(DateTimeImmutable $day): bool
    {
        $day = $day->setTime(0, 0);

        return $day >= $this->startDate && $day <= $this->endDate;
    }
}
