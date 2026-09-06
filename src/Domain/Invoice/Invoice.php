<?php

declare(strict_types=1);

namespace App\Domain\Invoice;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use InvalidArgumentException;

/**
 * Facture émise **manuellement** pour un projet et une période (US-075, ADR-0022).
 *
 * Facturation minimale : montant en centimes, statut `issued`, figée à l'émission (INV-2) — jamais
 * réécrite. Échéances/encaissement/avoirs = tranche ultérieure. Portée par tenant (INV-1). Le client
 * est copié du projet à l'émission (peut être nul si le projet n'est pas rattaché).
 */
#[ORM\Entity]
#[ORM\Table(name: 'invoice')]
#[ORM\Index(name: 'idx_invoice_tenant_project_period', columns: ['tenant_id', 'project_ref', 'period'])]
class Invoice implements TenantOwned
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    private function __construct(
        TenantId $tenantId,
        #[ORM\Column(name: 'project_ref', length: 100)]
        private string $projectRef,
        #[ORM\Column(name: 'period', length: 7)]
        private string $period,
        #[ORM\Column(name: 'amount_cents', type: 'integer')]
        private int $amountCents,
        #[ORM\Column(name: 'client_id', type: 'guid', nullable: true)]
        private ?string $clientId,
        #[ORM\Column(name: 'issued_by', type: 'guid', nullable: true)]
        private ?string $issuedBy,
        #[ORM\Column(name: 'issued_at', type: 'datetime_immutable')]
        private DateTimeImmutable $issuedAt,
        #[ORM\Column(name: 'status', length: 20, enumType: InvoiceStatus::class)]
        private InvoiceStatus $status = InvoiceStatus::ISSUED,
    ) {
        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
    }

    /**
     * Émet une facture (montant strictement positif, figée).
     */
    public static function issue(
        TenantId $tenantId,
        string $projectRef,
        string $period,
        int $amountCents,
        ?string $clientId,
        ?string $issuedBy,
        DateTimeImmutable $issuedAt,
    ): self {
        if ($amountCents <= 0) {
            throw new InvalidArgumentException('Le montant de la facture doit être strictement positif.');
        }

        return new self($tenantId, $projectRef, $period, $amountCents, $clientId, $issuedBy, $issuedAt);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }

    public function projectRef(): string
    {
        return $this->projectRef;
    }

    public function period(): string
    {
        return $this->period;
    }

    public function amountCents(): int
    {
        return $this->amountCents;
    }

    public function clientId(): ?string
    {
        return $this->clientId;
    }

    public function issuedBy(): ?string
    {
        return $this->issuedBy;
    }

    public function issuedAt(): DateTimeImmutable
    {
        return $this->issuedAt;
    }

    public function status(): InvoiceStatus
    {
        return $this->status;
    }
}
