<?php

declare(strict_types=1);

namespace App\Domain\Period;

/**
 * Tentative de modification d'une imputation appartenant à une période clôturée (US-057, CA-4).
 *
 * Sans réouverture formelle (RG-TMP-6, INV-7), aucune modification n'est possible : traduite en
 * **423 Locked** côté API.
 */
final class PeriodLockedException extends PeriodException
{
}
