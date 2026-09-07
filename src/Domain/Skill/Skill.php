<?php

declare(strict_types=1);

namespace App\Domain\Skill;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

/**
 * US-013 (EF-REF-10) — compétence du référentiel tenant (catégorie + libellé). Désactivable (jamais
 * supprimée, RG-REF-1). Unicité par (tenant, catégorie, libellé normalisé, insensible à la casse).
 */
#[ORM\Entity]
#[ORM\Table(name: 'skill')]
#[ORM\UniqueConstraint(name: 'uniq_skill_tenant_category_label', columns: ['tenant_id', 'category', 'normalized_label'])]
class Skill implements TenantOwned
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    #[ORM\Column(name: 'normalized_label', length: 255)]
    private string $normalizedLabel;

    #[ORM\Column(name: 'active', type: 'boolean')]
    private bool $active = true;

    public function __construct(
        TenantId $tenantId,
        #[ORM\Column(name: 'category', length: 20, enumType: SkillCategory::class)]
        private SkillCategory $category,
        #[ORM\Column(name: 'label', length: 255)]
        private string $label,
    ) {
        $label = trim($label);
        if ('' === $label) {
            throw new InvalidArgumentException('Le libellé de la compétence est obligatoire.');
        }
        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
        $this->label = $label;
        $this->normalizedLabel = self::normalize($label);
    }

    public static function normalize(string $label): string
    {
        return mb_strtolower(trim($label));
    }

    public function deactivate(): void
    {
        $this->active = false;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }

    public function category(): SkillCategory
    {
        return $this->category;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function isActive(): bool
    {
        return $this->active;
    }
}
