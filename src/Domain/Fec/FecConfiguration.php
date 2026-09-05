<?php

declare(strict_types=1);

namespace App\Domain\Fec;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use InvalidArgumentException;

/**
 * Configuration comptable d'un tenant pour l'export FEC (US-074, ADR-0021).
 *
 * Porte le SIREN (nommage `<SIREN>FEC<AAAAMMJJ>.txt`), le journal et le mapping des 4 comptes utilisés
 * pour dériver des écritures équilibrées à partir des marges figées : produit (CA), tiers (contrepartie
 * du CA), charge (coût), contrepartie de charge. Une configuration par tenant.
 */
#[ORM\Entity]
#[ORM\Table(name: 'fec_configuration')]
#[ORM\UniqueConstraint(name: 'uniq_fec_config_tenant', columns: ['tenant_id'])]
class FecConfiguration implements TenantOwned
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    public function __construct(
        TenantId $tenantId,
        #[ORM\Column(length: 9)]
        private string $siren,
        #[ORM\Column(name: 'journal_code', length: 10)]
        private string $journalCode,
        #[ORM\Column(name: 'journal_lib', length: 100)]
        private string $journalLib,
        #[ORM\Column(name: 'revenue_account_num', length: 20)]
        private string $revenueAccountNum,
        #[ORM\Column(name: 'revenue_account_lib', length: 100)]
        private string $revenueAccountLib,
        #[ORM\Column(name: 'receivable_account_num', length: 20)]
        private string $receivableAccountNum,
        #[ORM\Column(name: 'receivable_account_lib', length: 100)]
        private string $receivableAccountLib,
        #[ORM\Column(name: 'cost_account_num', length: 20)]
        private string $costAccountNum,
        #[ORM\Column(name: 'cost_account_lib', length: 100)]
        private string $costAccountLib,
        #[ORM\Column(name: 'cost_counterpart_account_num', length: 20)]
        private string $costCounterpartAccountNum,
        #[ORM\Column(name: 'cost_counterpart_account_lib', length: 100)]
        private string $costCounterpartAccountLib,
    ) {
        if (1 !== preg_match('/^\d{9}$/', $siren)) {
            throw new InvalidArgumentException('Le SIREN doit comporter 9 chiffres.');
        }
        foreach (['journalCode' => $journalCode, 'revenueAccountNum' => $revenueAccountNum, 'receivableAccountNum' => $receivableAccountNum, 'costAccountNum' => $costAccountNum, 'costCounterpartAccountNum' => $costCounterpartAccountNum] as $label => $value) {
            if ('' === trim($value)) {
                throw new InvalidArgumentException(sprintf('Configuration FEC incomplète : %s est requis.', $label));
            }
        }

        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
    }

    /**
     * Met à jour la configuration en place (édition tenant) avec les mêmes invariants qu'à la création.
     */
    public function reconfigure(
        string $siren,
        string $journalCode,
        string $journalLib,
        string $revenueAccountNum,
        string $revenueAccountLib,
        string $receivableAccountNum,
        string $receivableAccountLib,
        string $costAccountNum,
        string $costAccountLib,
        string $costCounterpartAccountNum,
        string $costCounterpartAccountLib,
    ): void {
        if (1 !== preg_match('/^\d{9}$/', $siren)) {
            throw new InvalidArgumentException('Le SIREN doit comporter 9 chiffres.');
        }
        foreach (['journalCode' => $journalCode, 'revenueAccountNum' => $revenueAccountNum, 'receivableAccountNum' => $receivableAccountNum, 'costAccountNum' => $costAccountNum, 'costCounterpartAccountNum' => $costCounterpartAccountNum] as $label => $value) {
            if ('' === trim($value)) {
                throw new InvalidArgumentException(sprintf('Configuration FEC incomplète : %s est requis.', $label));
            }
        }

        $this->siren = $siren;
        $this->journalCode = $journalCode;
        $this->journalLib = $journalLib;
        $this->revenueAccountNum = $revenueAccountNum;
        $this->revenueAccountLib = $revenueAccountLib;
        $this->receivableAccountNum = $receivableAccountNum;
        $this->receivableAccountLib = $receivableAccountLib;
        $this->costAccountNum = $costAccountNum;
        $this->costAccountLib = $costAccountLib;
        $this->costCounterpartAccountNum = $costCounterpartAccountNum;
        $this->costCounterpartAccountLib = $costCounterpartAccountLib;
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }

    public function siren(): string
    {
        return $this->siren;
    }

    public function journalCode(): string
    {
        return $this->journalCode;
    }

    public function journalLib(): string
    {
        return $this->journalLib;
    }

    public function revenueAccountNum(): string
    {
        return $this->revenueAccountNum;
    }

    public function revenueAccountLib(): string
    {
        return $this->revenueAccountLib;
    }

    public function receivableAccountNum(): string
    {
        return $this->receivableAccountNum;
    }

    public function receivableAccountLib(): string
    {
        return $this->receivableAccountLib;
    }

    public function costAccountNum(): string
    {
        return $this->costAccountNum;
    }

    public function costAccountLib(): string
    {
        return $this->costAccountLib;
    }

    public function costCounterpartAccountNum(): string
    {
        return $this->costCounterpartAccountNum;
    }

    public function costCounterpartAccountLib(): string
    {
        return $this->costCounterpartAccountLib;
    }
}
