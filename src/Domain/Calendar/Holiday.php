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
 * US-012 (EF-REF-6) — jour férié du tenant. Exclu des jours ouvrés par {@see WorkingDaysCalculator}
 * (capacité, occupation, complétude, relances). Unicité par (tenant, date).
 */
#[ORM\Entity]
#[ORM\Table(name: 'holiday')]
#[ORM\UniqueConstraint(name: 'uniq_holiday_tenant_date', columns: ['tenant_id', 'date'])]
class Holiday implements TenantOwned
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    public function __construct(
        TenantId $tenantId,
        #[ORM\Column(name: 'date', type: 'date_immutable')]
        private DateTimeImmutable $date,
        #[ORM\Column(name: 'label', length: 255)]
        private string $label,
    ) {
        $label = trim($label);
        if ('' === $label) {
            throw new InvalidArgumentException('Le libellé du jour férié est obligatoire.');
        }
        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
        $this->date = $date->setTime(0, 0);
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

    public function date(): DateTimeImmutable
    {
        return $this->date;
    }

    public function label(): string
    {
        return $this->label;
    }
}
