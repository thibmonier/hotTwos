<?php

declare(strict_types=1);

namespace App\Application\User;

use App\Domain\User\User;
use DateTimeInterface;

/**
 * US-068 (T-068-06) — port d'envoi du lien de réinitialisation de mot de passe (DIP).
 *
 * L'URL de réinitialisation est déjà résolue par l'appelant (le contrôleur, qui seul connaît le
 * routeur) : l'adaptateur n'a qu'à composer et livrer le message. Permet un test double enregistreur
 * (comme {@see \App\Domain\Reminder\ReminderNotifier}).
 */
interface PasswordResetMailer
{
    public function sendResetLink(User $user, string $resetUrl, DateTimeInterface $expiresAt): void;
}
