<?php

declare(strict_types=1);

namespace App\Application\User;

use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * US-068 (T-068-11) — politique de mot de passe partagée (réinitialisation + « Mon compte »).
 *
 * Centralise les règles (DRY) : longueur 12–128 et **non-compromission** (HaveIBeenPwned via
 * k-anonymity, OWASP). `skipOnError` = **fail-open** : si l'API est injoignable, on ne bloque pas
 * l'utilisateur (la disponibilité prime ; la longueur + Argon2id restent garanties).
 */
final readonly class PasswordPolicy
{
    public const int MIN_LENGTH = 12;
    public const int MAX_LENGTH = 128;

    public function __construct(private ValidatorInterface $validator)
    {
    }

    /**
     * @return string|null message d'erreur (générique) si le mot de passe est refusé, sinon null
     */
    public function violation(string $password): ?string
    {
        $length = mb_strlen($password);
        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            return sprintf('Le mot de passe doit contenir entre %d et %d caractères.', self::MIN_LENGTH, self::MAX_LENGTH);
        }

        if (count($this->validator->validate($password, new NotCompromisedPassword(skipOnError: true))) > 0) {
            return 'Ce mot de passe figure dans une fuite de données connue. Choisissez-en un autre.';
        }

        return null;
    }
}
