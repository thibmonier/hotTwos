<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

/**
 * Profil de tarification (US-011, EF-REF-4) : porte le mode de calcul du coût de revient.
 *
 * Les valeurs tarifaires (coût, taux de vente) ne sont pas portées ici mais par
 * {@see ProfileRate}, historisées à date d'effet — un profil ne modifie jamais ses valeurs
 * passées lors d'une révision. Portée par tenant (INV-1), désactivable (RG-REF-1).
 */
#[ORM\Entity]
#[ORM\Table(name: 'profile')]
#[ORM\Index(name: 'idx_profile_tenant', columns: ['tenant_id'])]
class Profile implements TenantOwned
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'boolean')]
    private bool $active;

    public function __construct(TenantId $tenantId, string $name, #[ORM\Column(name: 'calculation_mode', length: 20, enumType: CalculationMode::class)]
        private CalculationMode $calculationMode)
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
        $this->name = $this->guardName($name);
        $this->active = true;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function calculationMode(): CalculationMode
    {
        return $this->calculationMode;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function rename(string $name): void
    {
        $this->name = $this->guardName($name);
    }

    public function changeCalculationMode(CalculationMode $calculationMode): void
    {
        $this->calculationMode = $calculationMode;
    }

    public function deactivate(): void
    {
        $this->active = false;
    }

    public function activate(): void
    {
        $this->active = true;
    }

    private function guardName(string $name): string
    {
        $trimmed = trim($name);
        if ('' === $trimmed) {
            throw new InvalidArgumentException('Le nom d\'un profil est obligatoire.');
        }
        if (mb_strlen($trimmed) > 255) {
            throw new InvalidArgumentException('Le nom d\'un profil ne peut pas dépasser 255 caractères.');
        }

        return $trimmed;
    }
}
