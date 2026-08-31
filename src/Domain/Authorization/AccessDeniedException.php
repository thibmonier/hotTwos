<?php

declare(strict_types=1);

namespace App\Domain\Authorization;

use RuntimeException;

/**
 * Habilitation refusée par la couche métier (ARC-19, ARC-106).
 *
 * Levée par l'{@see \App\Application\Authorization\Authorizer} — jamais par l'UI.
 * Le pont HTTP la traduit en 403 Forbidden ; le message est le motif fonctionnel
 * exposé au client (ex. « Permission refusée : delete:project »).
 */
final class AccessDeniedException extends RuntimeException
{
}
