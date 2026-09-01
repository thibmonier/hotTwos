<?php

declare(strict_types=1);

namespace App\Application\Reminder\Message;

use App\Application\Messaging\TenantAwareMessage;
use App\Domain\Tenant\TenantId;
use DateTimeImmutable;

/**
 * Ordre de calcul et d'émission des relances dues d'un tenant à un instant donné (US-056, T-056-03).
 *
 * Publié par la commande cron {@see \App\UI\Cli\RunRemindersCommand}, consommé de façon **asynchrone**
 * hors requête HTTP. Porteur de son tenant (ARC-47) : le middleware pose le contexte à la consommation
 * (filtre ORM **et** `app.current_tenant` pour la RLS), condition des lectures et de l'écriture du
 * journal dans le chemin worker. L'instant `$now` est figé au moment du déclenchement du cron pour
 * garder un calcul de fréquence déterministe même si la consommation est différée.
 */
final readonly class SendDueReminders implements TenantAwareMessage
{
    public function __construct(
        private string $tenantId,
        private DateTimeImmutable $now,
    ) {
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}
