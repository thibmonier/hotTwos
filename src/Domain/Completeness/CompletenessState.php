<?php

declare(strict_types=1);

namespace App\Domain\Completeness;

/**
 * État de complétude d'une semaine pour un collaborateur (US-058, EF-TMP-24).
 *
 * `submitted` : 100 % des jours ouvrés attendus saisis. `partial` : 1 à 99 %. `empty_late` :
 * 0 % et délai J+2 dépassé. `in_progress` : délai J+2 non atteint.
 */
enum CompletenessState: string
{
    case SUBMITTED = 'submitted';
    case PARTIAL = 'partial';
    case EMPTY_LATE = 'empty_late';
    case IN_PROGRESS = 'in_progress';
}
