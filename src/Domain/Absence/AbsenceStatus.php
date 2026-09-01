<?php

declare(strict_types=1);

namespace App\Domain\Absence;

/**
 * Statut d'une demande d'absence (US-054, EF-TMP-15).
 */
enum AbsenceStatus: string
{
    case PENDING = 'pending';
    case VALIDATED = 'validated';
    case REJECTED = 'rejected';
}
