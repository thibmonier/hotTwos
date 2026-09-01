<?php

declare(strict_types=1);

namespace App\Domain\Activity;

/**
 * Type d'activité d'une imputation (US-059, EF-TMP-27). Le modèle actuel ne porte pas de taxonomie
 * fine d'activités : le seul axe disponible est **production** vs **absence** (projet système
 * « Absence »). Une taxonomie détaillée relèvera d'un enrichissement ultérieur (type de projet).
 */
enum ActivityType: string
{
    case PRODUCTION = 'production';
    case ABSENCE = 'absence';
}
