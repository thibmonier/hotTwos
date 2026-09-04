<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Domain\Tenant\TenantId;
use App\Domain\Tenant\TenantOwned;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use InvalidArgumentException;
use LogicException;

/**
 * Utilisateur, porté par un tenant (INV-1, TenantOwned) — deux tenants peuvent avoir
 * un utilisateur du même e-mail (unicité par tenant, pas globale).
 *
 * Mot de passe stocké haché avec Argon2id (jamais en clair — ENF-SEC-3, rules/11-security).
 */
#[ORM\Entity]
#[ORM\Table(name: 'app_user')]
#[ORM\UniqueConstraint(name: 'uniq_user_tenant_email', columns: ['tenant_id', 'email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface, TenantOwned
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'tenant_id', type: 'guid')]
    private string $tenantId;

    #[ORM\Column(length: 180)]
    private string $email;

    #[ORM\Column(name: 'first_name', length: 100, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(name: 'last_name', length: 100, nullable: true)]
    private ?string $lastName = null;

    /**
     * @param list<string> $roles
     */
    public function __construct(TenantId $tenantId, string $email, #[ORM\Column]
        private string $password, #[ORM\Column(type: 'json')]
        private array $roles = ['ROLE_USER'])
    {
        if ('' === $email) {
            throw new InvalidArgumentException('L\'e-mail de l\'utilisateur ne peut pas être vide.');
        }

        $this->id = Uuid::v7()->toRfc4122();
        $this->tenantId = $tenantId->toString();
        $this->email = $email;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }

    public function email(): string
    {
        return $this->email;
    }

    public function firstName(): ?string
    {
        return $this->firstName;
    }

    public function lastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * Nom d'affichage « Prénom Nom » (US-067). Retombe sur l'e-mail si aucun nom n'est renseigné
     * (rétrocompatibilité CA-3 : jamais « null null », toujours identifiable).
     */
    public function displayName(): string
    {
        $name = trim(($this->firstName ?? '').' '.($this->lastName ?? ''));

        return '' !== $name ? $name : $this->email;
    }

    /**
     * Renseigne le prénom et le nom (US-067, CA-2/CA-4). Les valeurs sont nettoyées ; une valeur vide
     * est stockée comme absente (null). Longueur maximale : 100 caractères par champ.
     */
    public function rename(?string $firstName, ?string $lastName): void
    {
        $this->firstName = $this->normalizeName($firstName);
        $this->lastName = $this->normalizeName($lastName);
    }

    private function normalizeName(?string $value): ?string
    {
        if (null === $value) {
            return null;
        }

        $trimmed = trim($value);
        if ('' === $trimmed) {
            return null;
        }
        if (mb_strlen($trimmed) > 100) {
            throw new InvalidArgumentException('Le nom ne peut pas dépasser 100 caractères.');
        }

        return $trimmed;
    }

    public function getUserIdentifier(): string
    {
        if ('' === $this->email) {
            throw new LogicException('Utilisateur sans e-mail : identifiant indisponible.');
        }

        return $this->email;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Remplace le mot de passe (déjà haché en Argon2id par l'appelant — US-068, jamais de clair ici).
     */
    public function changePassword(string $hashedPassword): void
    {
        if ('' === $hashedPassword) {
            throw new InvalidArgumentException('Le mot de passe haché ne peut pas être vide.');
        }

        $this->password = $hashedPassword;
    }

    public function eraseCredentials(): void
    {
    }
}
