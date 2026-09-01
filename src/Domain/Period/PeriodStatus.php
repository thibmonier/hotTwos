<?php

declare(strict_types=1);

namespace App\Domain\Period;

/**
 * Statut d'une période comptable (US-057).
 *
 * `open` : saisies et modifications autorisées. `closing` : clôture en cours (calculs aval
 * asynchrones). `closed` : imputations verrouillées (LOCKED), modification impossible sans
 * réouverture formelle (RG-TMP-6, INV-7).
 */
enum PeriodStatus: string
{
    case OPEN = 'open';
    case CLOSING = 'closing';
    case CLOSED = 'closed';
}
