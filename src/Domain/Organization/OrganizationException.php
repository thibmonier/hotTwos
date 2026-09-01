<?php

declare(strict_types=1);

namespace App\Domain\Organization;

use RuntimeException;

/**
 * Erreur métier de l'organisation (US-010) : cycle de hiérarchie, chevauchement de
 * rattachements, unité introuvable ou désactivée.
 */
class OrganizationException extends RuntimeException
{
}
