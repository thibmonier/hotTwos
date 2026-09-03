<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Domain\Tenant\TenantId;

/**
 * Port de lecture des utilisateurs (DIP). Tenant passé explicitement : permet de valider
 * qu'un identifiant de collaborateur appartient bien au tenant courant (deny by default).
 */
interface UserRepository
{
    public function existsInTenant(TenantId $tenant, string $userId): bool;

    /**
     * Identifiants de tous les collaborateurs d'un tenant (périmètre « équipe » du pilotage — US-058).
     *
     * @return list<string>
     */
    public function findIdsByTenant(TenantId $tenant): array;

    /**
     * Résout les adresses e-mail d'un ensemble d'utilisateurs, bornée au tenant (F-S5-4 : identifier
     * les collaborateurs par leur e-mail plutôt que par un identifiant technique).
     *
     * @param list<string> $userIds
     *
     * @return array<string, string> map userId => email (les ids inconnus ou hors tenant sont absents)
     */
    public function findEmailsByIds(TenantId $tenant, array $userIds): array;

    /**
     * Noms d'affichage « Prénom Nom » par identifiant (US-067), avec repli sur l'e-mail si non renseignés.
     *
     * @param list<string> $userIds
     *
     * @return array<string, string> id → nom d'affichage
     */
    public function findDisplayNamesByIds(TenantId $tenant, array $userIds): array;
}
