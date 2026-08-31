<?php

declare(strict_types=1);

namespace App\Domain\Tenant;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entité racine du modèle (INV-1) : tout tenant est isolé des autres (ENF-SEC-4).
 *
 * Note d'architecture (ADR-8, dosage) : le mapping Doctrine est porté par attributs
 * sur l'entité de domaine — compromis pragmatique assumé pour le socle.
 */
#[ORM\Entity]
#[ORM\Table(name: 'tenant')]
class Tenant
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    public function __construct(TenantId $id, #[ORM\Column(length: 255)]
        private string $name)
    {
        $this->id = $id->toString();
    }

    public function id(): TenantId
    {
        return TenantId::fromString($this->id);
    }

    public function name(): string
    {
        return $this->name;
    }
}
