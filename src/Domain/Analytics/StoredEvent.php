<?php

declare(strict_types=1);

namespace App\Domain\Analytics;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use DateTimeImmutable;

/**
 * Événement persisté du flux (event stream) — source de vérité append-only qui alimente
 * les projections analytiques (ADR-9). Porté par tenant (INV-1, TenantOwned).
 *
 * Le flux est immuable : la reconstruction du modèle (ARC-114) rejoue ces événements ;
 * elle n'y touche jamais.
 */
#[ORM\Entity]
#[ORM\Table(name: 'event_stream')]
#[ORM\Index(name: 'idx_event_stream_tenant_seq', columns: ['tenant_id', 'sequence'])]
class StoredEvent implements TenantOwned
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    #[ORM\Column(length: 100)]
    private string $name;

    /** @var array<string, scalar> */
    #[ORM\Column(type: 'json')]
    private array $payload;

    #[ORM\Column(name: 'occurred_at', type: 'datetime_immutable')]
    private DateTimeImmutable $occurredAt;

    public function __construct(DomainEvent $event, /** Ordre total d'application par tenant (déterminisme de la reconstruction). */
        #[ORM\Column(type: 'integer')]
        private int $sequence)
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $event->tenantId()->toString();
        $this->name = $event->name();
        $this->payload = $event->payload();
        $this->occurredAt = $event->occurredAt();
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, scalar>
     */
    public function payload(): array
    {
        return $this->payload;
    }

    public function sequence(): int
    {
        return $this->sequence;
    }
}
