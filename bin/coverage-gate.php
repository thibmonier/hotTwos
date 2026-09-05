<?php

declare(strict_types=1);

/**
 * QUAL-2 — Gate de couverture : échoue (exit 1) si le taux de lignes < seuil.
 *
 * Usage : php bin/coverage-gate.php [coverage.xml] [seuil]
 * Le taux « lignes » = coveredstatements / statements des métriques agrégées du projet
 * (dans un rapport Clover, l'agrégat est la balise <metrics> au plus grand nombre de statements).
 */

$file = $argv[1] ?? 'coverage.xml';
$threshold = (float) ($argv[2] ?? 80.0);

if (!is_file($file)) {
    fwrite(\STDERR, sprintf("Rapport de couverture introuvable : %s\n", $file));
    exit(2);
}

$xml = simplexml_load_file($file);
if (false === $xml) {
    fwrite(\STDERR, "Rapport de couverture illisible (XML invalide).\n");
    exit(2);
}

$statements = 0;
$covered = 0;
foreach ($xml->xpath('//metrics') ?: [] as $metrics) {
    $s = (int) $metrics['statements'];
    if ($s > $statements) {
        $statements = $s;
        $covered = (int) $metrics['coveredstatements'];
    }
}

if (0 === $statements) {
    fwrite(\STDERR, "Aucune métrique de couverture trouvée.\n");
    exit(2);
}

$pct = $covered / $statements * 100;
printf("Couverture lignes : %d/%d = %.2f%% (seuil %.0f%%)\n", $covered, $statements, $pct, $threshold);

exit($pct >= $threshold ? 0 : 1);
