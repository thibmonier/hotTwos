<?php

declare(strict_types=1);

namespace App\Domain\Budget;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * US-079c (EF-PRJ-16) — photo de l'atterrissage en charge d'un projet à la clôture d'une période.
 *
 * Capturée par un handler **séparé** ({@see \App\Application\Budget\CaptureChargeLandingOnPeriodClosed})
 * sur l'événement de clôture : {@see \App\Application\Margin\ComputeProjectMargins} reste **inchangé**.
 * La série de snapshots alimente la courbe d'atterrissage.
 */
#[ORM\Entity]
#[ORM\Table(name: 'charge_landing_snapshot')]
#[ORM\UniqueConstraint(name: 'uniq_charge_landing_snapshot', columns: ['tenant_id', 'project_id', 'period'])]
class ChargeLandingSnapshot implements TenantOwned
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    private function __construct(
        TenantId $tenantId,
        #[ORM\Column(name: 'project_id', type: 'guid')]
        private string $projectId,
        #[ORM\Column(name: 'period', length: 7)]
        private string $period,
        #[ORM\Column(name: 'project_name', length: 255)]
        private string $projectName,
        #[ORM\Column(name: 'landing_cost_cents', type: 'integer', nullable: true)]
        private ?int $landingCostCents,
        #[ORM\Column(name: 'cost_budget_cents', type: 'integer', nullable: true)]
        private ?int $costBudgetCents,
        #[ORM\Column(name: 'overrun_percent', type: 'float', nullable: true)]
        private ?float $overrunPercent,
        #[ORM\Column(name: 'consumption_percent', type: 'float', nullable: true)]
        private ?float $consumptionPercent,
        #[ORM\Column(name: 'physical_progress_percent', type: 'smallint', nullable: true)]
        private ?int $physicalProgressPercent,
        #[ORM\Column(name: 'is_early_drift', type: 'boolean')]
        private bool $isEarlyDrift,
        #[ORM\Column(name: 'is_escalated', type: 'boolean')]
        private bool $isEscalated,
        #[ORM\Column(name: 'captured_at', type: 'datetime_immutable')]
        private DateTimeImmutable $capturedAt,
    ) {
        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
    }

    public static function capture(
        TenantId $tenantId,
        string $period,
        string $projectId,
        string $projectName,
        ChargeLanding $landing,
        DateTimeImmutable $capturedAt,
    ): self {
        return new self(
            $tenantId,
            $projectId,
            $period,
            $projectName,
            $landing->landingCostCents,
            $landing->costBudgetCents,
            $landing->overrunPercent,
            $landing->consumptionPercent,
            $landing->physicalProgressPercent,
            $landing->isEarlyDrift,
            $landing->isEscalated,
            $capturedAt,
        );
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }

    public function projectId(): string
    {
        return $this->projectId;
    }

    public function period(): string
    {
        return $this->period;
    }

    public function projectName(): string
    {
        return $this->projectName;
    }

    public function landingCostCents(): ?int
    {
        return $this->landingCostCents;
    }

    public function costBudgetCents(): ?int
    {
        return $this->costBudgetCents;
    }

    public function overrunPercent(): ?float
    {
        return $this->overrunPercent;
    }

    public function consumptionPercent(): ?float
    {
        return $this->consumptionPercent;
    }

    public function physicalProgressPercent(): ?int
    {
        return $this->physicalProgressPercent;
    }

    public function isEarlyDrift(): bool
    {
        return $this->isEarlyDrift;
    }

    public function isEscalated(): bool
    {
        return $this->isEscalated;
    }

    public function capturedAt(): DateTimeImmutable
    {
        return $this->capturedAt;
    }
}
