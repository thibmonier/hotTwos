<?php

declare(strict_types=1);

namespace App\Domain\Project;

use RuntimeException;

/**
 * Erreur métier du module projet (US-030+) — traduite en 422 par le listener HTTP.
 */
class ProjectException extends RuntimeException
{
}
