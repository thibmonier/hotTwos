<?php

declare(strict_types=1);

namespace App\Domain\Timesheet;

/**
 * Cycle de validation d'une imputation (US-055) : soumise par défaut, puis validée ou
 * refusée (avec motif) par le responsable du projet.
 */
enum ValidationStatus: string
{
    case PENDING = 'pending';
    case VALIDATED = 'validated';
    case REJECTED = 'rejected';
}
