<?php

declare(strict_types=1);

namespace App\Domain\Currency;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Devise de référence d'un tenant (US-016, EF-REF-22) : la devise dans laquelle tous les montants sont
 * consolidés. Une seule par tenant ; défaut EUR en l'absence de configuration (voir {@see CurrencyConverter}).
 */
#[ORM\Entity]
#[ORM\Table(name: 'reference_currency')]
#[ORM\UniqueConstraint(name: 'uniq_reference_currency_tenant', columns: ['tenant_id'])]
class ReferenceCurrency implements TenantOwned
{
    public const string DEFAULT_CODE = 'EUR';

    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    public function __construct(
        TenantId $tenantId,
        #[ORM\Column(name: 'code', length: 3)]
        private string $code,
    ) {
        $this->code = $this->guardCode($code);
        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
    }

    public function change(string $code): void
    {
        $this->code = $this->guardCode($code);
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }

    public function code(): string
    {
        return $this->code;
    }

    private function guardCode(string $code): string
    {
        $code = strtoupper(trim($code));
        if (1 !== preg_match('/^[A-Z]{3}$/', $code)) {
            throw new CurrencyException('Code devise invalide (attendu 3 lettres ISO 4217, ex. EUR).');
        }

        return $code;
    }
}
