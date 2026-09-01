<?php

declare(strict_types=1);

namespace App\Domain\Valuation;

/**
 * Tentative de recalcul de valorisation sur une période clôturée (US-060, CA-5).
 *
 * Sans réouverture formelle (US-057), aucun recalcul n'est possible : traduite en
 * **423 Locked** côté API, elle protège l'intégrité des données historiques figées.
 */
final class PeriodClosedException extends ValuationException
{
}
