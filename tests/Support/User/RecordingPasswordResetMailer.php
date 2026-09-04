<?php

declare(strict_types=1);

namespace App\Tests\Support\User;

use App\Application\User\PasswordResetMailer;
use App\Domain\User\User;
use DateTimeInterface;

/**
 * Test double enregistreur du {@see PasswordResetMailer} (US-068) : capture les liens de
 * réinitialisation envoyés, sans envoi réel. Modèle : `RecordingReminderNotifier`.
 */
final class RecordingPasswordResetMailer implements PasswordResetMailer
{
    /** @var list<array{user: User, url: string, expiresAt: DateTimeInterface}> */
    public array $sent = [];

    public function sendResetLink(User $user, string $resetUrl, DateTimeInterface $expiresAt): void
    {
        $this->sent[] = ['user' => $user, 'url' => $resetUrl, 'expiresAt' => $expiresAt];
    }

    public function lastUrl(): ?string
    {
        $last = end($this->sent);

        return false === $last ? null : $last['url'];
    }
}
