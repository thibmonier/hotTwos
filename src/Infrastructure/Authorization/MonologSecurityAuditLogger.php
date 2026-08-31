<?php

declare(strict_types=1);

namespace App\Infrastructure\Authorization;

use App\Domain\Authorization\SecurityAuditLogger;
use Psr\Log\LoggerInterface;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * Implémentation du {@see SecurityAuditLogger} sur le canal Monolog dédié « security »
 * (US-003, HAB-6, ARC-75) : piste d'audit séparée, toujours émise.
 *
 * Chaque enregistrement porte qui (acteur), quoi (événement + contexte), quand
 * (horodatage précis) et le tenant concerné.
 */
final readonly class MonologSecurityAuditLogger implements SecurityAuditLogger
{
    public function __construct(private LoggerInterface $securityLogger)
    {
    }

    public function record(string $event, string $tenantId, ?string $actorId, array $context = []): void
    {
        $this->securityLogger->info($event, [
            'event' => $event,
            'tenant_id' => $tenantId,
            'actor' => $actorId,
            'at' => new DateTimeImmutable()->format(DateTimeInterface::ATOM),
            ...$context,
        ]);
    }
}
