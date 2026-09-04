<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\ResetPassword;

use App\Application\User\PasswordResetMailer;
use App\Domain\User\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use DateTimeInterface;

/**
 * US-068 (T-068-06) — adaptateur d'envoi du lien de réinitialisation via `symfony/mailer`.
 *
 * Compose un e-mail HTML (template Twig) adressé au compte concerné. L'expéditeur est configuré
 * (`%app.mail_from%`). Aucune donnée sensible dans le message hormis le lien à durée de vie limitée.
 */
final readonly class SymfonyPasswordResetMailer implements PasswordResetMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private string $fromAddress,
        private string $fromName,
    ) {
    }

    public function sendResetLink(User $user, string $resetUrl, DateTimeInterface $expiresAt): void
    {
        $email = new TemplatedEmail()
            ->from(new Address($this->fromAddress, $this->fromName))
            ->to($user->email())
            ->subject('Réinitialisation de votre mot de passe')
            ->htmlTemplate('emails/reset_password.html.twig')
            ->context([
                'resetUrl' => $resetUrl,
                'displayName' => $user->displayName(),
                'expiresAt' => $expiresAt,
            ]);

        $this->mailer->send($email);
    }
}
