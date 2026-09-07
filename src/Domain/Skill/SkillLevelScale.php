<?php

declare(strict_types=1);

namespace App\Domain\Skill;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

/**
 * US-013 (EF-REF-11) — échelle de niveaux de compétence paramétrable, une par tenant. Les niveaux sont
 * ordonnés (index 1..n). Valeur par défaut : 4 niveaux.
 */
#[ORM\Entity]
#[ORM\Table(name: 'skill_level_scale')]
#[ORM\UniqueConstraint(name: 'uniq_skill_level_scale_tenant', columns: ['tenant_id'])]
class SkillLevelScale implements TenantOwned
{
    public const array DEFAULT_LEVELS = ['Débutant', 'Intermédiaire', 'Avancé', 'Expert'];

    private const int MAX_LEVELS = 10;

    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    /**
     * @param list<string> $levels
     */
    public function __construct(
        TenantId $tenantId,
        #[ORM\Column(name: 'levels', type: 'json')]
        private array $levels = self::DEFAULT_LEVELS,
    ) {
        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
        $this->levels = $this->clean($levels);
    }

    /**
     * @param list<string> $levels
     */
    public function reconfigure(array $levels): void
    {
        $this->levels = $this->clean($levels);
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }

    /**
     * @return list<string>
     */
    public function levels(): array
    {
        return $this->levels;
    }

    public function count(): int
    {
        return count($this->levels);
    }

    public function isValidLevel(int $level): bool
    {
        return $level >= 1 && $level <= count($this->levels);
    }

    /**
     * @param list<string> $levels
     *
     * @return list<string>
     */
    private function clean(array $levels): array
    {
        $clean = array_values(array_filter(array_map(trim(...), $levels), static fn (string $l): bool => '' !== $l));
        if (count($clean) < 1 || count($clean) > self::MAX_LEVELS) {
            throw new InvalidArgumentException(sprintf('L\'échelle doit comporter entre 1 et %d niveaux non vides.', self::MAX_LEVELS));
        }

        return $clean;
    }
}
