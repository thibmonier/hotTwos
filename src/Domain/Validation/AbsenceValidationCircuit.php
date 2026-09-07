<?php

declare(strict_types=1);

namespace App\Domain\Validation;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

/**
 * US-017 (EF-REF-25) — circuit de validation des absences paramétrable : liste ordonnée d'étapes, chaque
 * étape désignée par un **rôle validateur**. Borné à 1..2 étapes. En l'absence de circuit configuré, le
 * comportement historique s'applique (1 étape, tout titulaire de `VALIDATE_ABSENCE`).
 */
#[ORM\Entity]
#[ORM\Table(name: 'absence_validation_circuit')]
#[ORM\UniqueConstraint(name: 'uniq_absence_validation_circuit_tenant', columns: ['tenant_id'])]
class AbsenceValidationCircuit implements TenantOwned
{
    private const int MAX_STEPS = 2;

    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    /**
     * @param list<string> $steps rôles validateurs, dans l'ordre des étapes
     */
    public function __construct(
        TenantId $tenantId,
        #[ORM\Column(name: 'steps', type: 'json')]
        private array $steps,
    ) {
        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
        $this->steps = $this->clean($steps);
    }

    /**
     * @param list<string> $steps
     */
    public function reconfigure(array $steps): void
    {
        $this->steps = $this->clean($steps);
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }

    /**
     * @return list<string>
     */
    public function steps(): array
    {
        return $this->steps;
    }

    public function stepCount(): int
    {
        return count($this->steps);
    }

    /** Rôle requis pour l'étape 1-based donnée. */
    public function roleForStep(int $step): string
    {
        return $this->steps[$step - 1] ?? '';
    }

    /**
     * @param list<string> $steps
     *
     * @return list<string>
     */
    private function clean(array $steps): array
    {
        $clean = array_values(array_filter(array_map(trim(...), $steps), static fn (string $r): bool => '' !== $r));
        if (count($clean) < 1 || count($clean) > self::MAX_STEPS) {
            throw new InvalidArgumentException(sprintf('Le circuit doit comporter entre 1 et %d étape(s), chacune avec un rôle validateur.', self::MAX_STEPS));
        }

        return $clean;
    }
}
