<?php

declare(strict_types=1);

namespace App\Domain\Period;

/**
 * Statut d'une demande de réouverture de période (US-057, CA-2).
 */
enum ReopeningStatus: string
{
    case REQUESTED = 'requested';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
}
