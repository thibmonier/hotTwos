<?php

declare(strict_types=1);

namespace App\Domain\Authorization;

/**
 * Périmètre de données — axe orthogonal aux permissions (US-003, HAB-1..6).
 *
 * Une permission dit *quelle action* est permise ; le périmètre dit *sur quelles
 * instances* elle s'applique. Les niveaux sont totalement ordonnés : un périmètre
 * plus large « couvre » les plus étroits. Cet ordre fonde l'anti-élévation de
 * privilège (CA-6) : un auteur ne peut accorder qu'un rôle dont le périmètre est
 * couvert par le sien.
 */
enum DataScope: string
{
    /** Ses seules données personnelles (P1 — collaborateur). */
    case OWN = 'own';

    /** Les projets dont l'utilisateur est responsable (P2 — chef de projet). */
    case OWN_PROJECTS = 'own_projects';

    /** Son pôle / l'ensemble des collaborateurs suivis (P3 — resource manager). */
    case POOL = 'pool';

    /** Toutes les données du tenant (P6 — dirigeant, administrateur). */
    case TENANT = 'tenant';

    /**
     * Rang d'inclusion croissant. Deux niveaux ne sont comparables que par ce rang ;
     * aucune logique métier ne doit dépendre de l'ordre de déclaration des `case`.
     */
    public function rank(): int
    {
        return match ($this) {
            self::OWN => 1,
            self::OWN_PROJECTS => 2,
            self::POOL => 3,
            self::TENANT => 4,
        };
    }

    /**
     * Vrai si ce périmètre englobe `$other` (égal ou plus large).
     */
    public function covers(self $other): bool
    {
        return $this->rank() >= $other->rank();
    }
}
