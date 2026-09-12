<?php

declare(strict_types=1);

/*
 * US-093 — Gate d'accessibilité (WCAG 2.2 AA) pour la CI.
 *
 * Lit les rapports JSON pa11y (runner axe-core) d'un répertoire, agrège les VIOLATIONS
 * (type "error", hors color-contrast — cf. décision : contraste différé, faux positifs en file://),
 * et les compare à une baseline (violations héritées, non bloquantes).
 *
 * Politique (décision PO 2026-09-12) : bloquer les NOUVELLES violations, tolérer la baseline.
 * Tant que la baseline n'est pas « établie » (revue du 1er rapport CI puis commit), le gate
 * fonctionne en MODE RAPPORT (affiche, n'échoue pas) — établissement de la baseline (plan Étape 3).
 *
 * Usage : php bin/a11y-gate.php <dir-rapports-json> <fichier-baseline-json>
 * Sortie : 0 si OK (ou mode rapport) ; 2 si nouvelles violations bloquantes.
 */

$reportsDir = $argv[1] ?? 'var/a11y/reports';
$baselineFile = $argv[2] ?? 'tests/a11y/baseline.json';

if (!is_dir($reportsDir)) {
    fwrite(STDERR, "a11y-gate : répertoire de rapports introuvable : {$reportsDir}\n");
    exit(1);
}

$baseline = ['established' => false, 'violations' => []];
if (is_file($baselineFile)) {
    $decoded = json_decode((string) file_get_contents($baselineFile), true);
    if (is_array($decoded)) {
        $baseline = $decoded + $baseline;
    }
}
/** @var list<string> $baselineKeys */
$baselineKeys = array_values(array_filter((array) ($baseline['violations'] ?? []), 'is_string'));
$established = (bool) ($baseline['established'] ?? false);

/** @var array<string, int> $found key "slug:code" => occurrences */
$found = [];
foreach (glob(rtrim($reportsDir, '/').'/*.json') ?: [] as $report) {
    $slug = basename($report, '.json');
    $data = json_decode((string) file_get_contents($report), true);
    if (!is_array($data)) {
        continue;
    }
    // pa11y --reporter json : liste d'issues { code, type, message, selector, ... }.
    foreach ($data as $issue) {
        if (!is_array($issue)) {
            continue;
        }
        $type = (string) ($issue['type'] ?? '');
        $code = (string) ($issue['code'] ?? '');
        if ('error' !== $type) {
            continue; // seules les erreurs (≈ serious/critical axe) bloquent ; warnings/notices ignorés
        }
        if (str_contains(strtolower($code), 'color-contrast')) {
            continue; // contraste différé (décision) — faux positifs en file:// sans CSS résolu
        }
        $key = $slug.':'.$code;
        $found[$key] = ($found[$key] ?? 0) + 1;
    }
}

$new = array_values(array_filter(array_keys($found), static fn (string $k): bool => !in_array($k, $baselineKeys, true)));
sort($new);

echo "── a11y-gate (WCAG 2.2 AA, axe via pa11y) ──\n";
echo sprintf("Rapports : %s · violations distinctes : %d · baseline : %d · établie : %s\n",
    $reportsDir, count($found), count($baselineKeys), $established ? 'oui' : 'non (mode rapport)');

if ([] !== $found) {
    echo "Violations détectées (slug:règle = occurrences) :\n";
    ksort($found);
    foreach ($found as $key => $n) {
        $flag = in_array($key, $baselineKeys, true) ? 'baseline' : 'NOUVELLE';
        echo sprintf("  - [%s] %s (×%d)\n", $flag, $key, $n);
    }
}

if (!$established) {
    echo "\nBaseline non établie → MODE RAPPORT (aucun échec). ";
    echo "Étape suivante : revoir ce rapport, committer la baseline (violations héritées) et passer established=true.\n";
    exit(0);
}

if ([] !== $new) {
    echo "\n❌ ".count($new)." NOUVELLE(S) violation(s) WCAG serious/critical (hors baseline) :\n";
    foreach ($new as $k) {
        echo "  - {$k}\n";
    }
    exit(2);
}

echo "\n✅ Aucune nouvelle violation WCAG serious/critical (hors baseline + contraste différé).\n";
exit(0);
