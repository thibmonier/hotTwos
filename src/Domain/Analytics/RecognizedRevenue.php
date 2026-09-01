<?php

declare(strict_types=1);

namespace App\Domain\Analytics;

/**
 * Réduction du flux d'événements en chiffre d'affaires reconnu par (période, projet) — US-060.
 *
 * Source **unique** de la sémantique de projection : le projecteur (qui matérialise
 * `fact_project_revenue`) et le vérificateur de non-divergence (ARC-113) l'appliquent tous
 * deux, ce qui garantit l'absence d'écart *par construction* — une seule règle de calcul, pas
 * deux implémentations à maintenir synchrones.
 *
 * Sémantique « dernière reconnaissance gagne » par imputation source : une imputation
 * re-valorisée (révision tarifaire appliquée après coup, re-déclenchement CA-4) **remplace**
 * sa reconnaissance précédente au lieu de s'y ajouter — jamais de double comptage. Les
 * reconnaissances sans imputation source (sonde US-005) sont additionnées telles quelles,
 * chacune étant une reconnaissance indépendante.
 */
final class RecognizedRevenue
{
    /**
     * @param list<StoredEvent> $stream flux ordonné par `sequence` croissante
     *
     * @return array<string, array<string, int>> période => (projet => centimes)
     */
    public static function byPeriodAndProject(array $stream): array
    {
        /** @var array<string, array{string, string, int}> $bySource dernière reconnaissance par imputation */
        $bySource = [];
        /** @var list<array{string, string, int}> $standalone reconnaissances sans source (sonde) */
        $standalone = [];

        foreach ($stream as $event) {
            if ('revenue_recognized' !== $event->name()) {
                continue;
            }

            $payload = $event->payload();
            $recognition = [(string) $payload['period'], (string) $payload['project_ref'], (int) $payload['amount_cents']];
            $source = $payload['source_time_entry_id'] ?? null;

            if (null === $source) {
                $standalone[] = $recognition;
            } else {
                // Flux ordonné par sequence : la dernière écriture écrase la précédente.
                $bySource[(string) $source] = $recognition;
            }
        }

        $totals = [];
        foreach ([...array_values($bySource), ...$standalone] as [$period, $projectRef, $cents]) {
            $totals[$period][$projectRef] = ($totals[$period][$projectRef] ?? 0) + $cents;
        }

        return $totals;
    }
}
