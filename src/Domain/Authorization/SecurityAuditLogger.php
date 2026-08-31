<?php

declare(strict_types=1);

namespace App\Domain\Authorization;

/**
 * Port de journalisation des événements de sécurité (HAB-6, ENF-SEC-5).
 *
 * Trace les accès sensibles et les tentatives non autorisées : qui, quoi, quand,
 * dans quel tenant. L'implémentation (canal dédié « security ») vit en infrastructure ;
 * le domaine ne connaît que ce contrat.
 */
interface SecurityAuditLogger
{
    /**
     * @param non-empty-string      $event   nom stable de l'événement (ex. « sensitive_data_read »)
     * @param array<string, string> $context détails structurés (ressource visée, permission, périmètre…)
     */
    public function record(string $event, string $tenantId, ?string $actorId, array $context = []): void;
}
