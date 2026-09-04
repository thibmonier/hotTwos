<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\ResetPassword;

use App\Domain\User\User;
use Doctrine\ORM\Mapping as ORM;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestTrait;
use Symfony\Component\Uid\Uuid;
use DateTimeInterface;

/**
 * US-068 (T-068-05) — jeton de réinitialisation de mot de passe (symfonycasts/reset-password-bundle).
 *
 * Stockage du couple selector/hashed_token + expiration, rattaché à un {@see User} précis. Le token
 * en clair n'est jamais persisté (seul son hash l'est), et la table n'est **pas** soumise au RLS
 * tenant : le flux « mot de passe oublié » est anonyme (aucun contexte tenant), la résolution passe
 * par le selector puis remonte au tenant via l'utilisateur lié.
 */
#[ORM\Entity]
#[ORM\Table(name: 'reset_password_request')]
class ResetPasswordRequest implements ResetPasswordRequestInterface
{
    use ResetPasswordRequestTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    public function __construct(#[ORM\ManyToOne(targetEntity: User::class)]
        #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
        private User $user, DateTimeInterface $expiresAt, string $selector, string $hashedToken)
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->initialize($expiresAt, $selector, $hashedToken);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUser(): object
    {
        return $this->user;
    }
}
