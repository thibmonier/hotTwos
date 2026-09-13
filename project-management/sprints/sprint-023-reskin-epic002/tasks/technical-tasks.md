# Tâches Techniques Transverses — Sprint 23

> Actions de la rétrospective S22, hors points (chantiers d'outillage).

## T-TECH-01 : `php-cs-fixer` dans le hook pre-commit
- **Type** : [OPS] · **Estimation** : 0.5h · **Quand** : J1 · **Origine** : rétro S22 ACTION-3 (report ACTION-4 S21, non faite)
- **Description** : ajouter au `.githooks/pre-commit` une étape `docker compose run --rm -T -e PHP_CS_FIXER_IGNORE_ENV=1 app php vendor/bin/php-cs-fixer fix --dry-run` après PHPStan/Deptrac, pour que le hook soit le miroir exact de la CI (qui bloque sur CS).
- **Critère** : un commit avec un `/** @var */` inline est bloqué par le hook ; `grep -c 'cs-fixer' .githooks/pre-commit` ≥ 1.

## T-TECH-02 : Stabiliser CodeQL `Analyze` en CI
- **Type** : [OPS] · **Estimation** : 2h · **Quand** : J5 · **Origine** : rétro S22 ACTION-2 (demande PO, incluse S23)
- **Description** : traiter l'instabilité de `Analyze (javascript-typescript | actions)` — augmenter l'espace disque du runner et/ou purger le cache `codeql-overlay-status-*` ; **ou**, si l'infra n'est pas fiabilisable, rendre ces jobs `continue-on-error: true` et documenter (fin de l'`UNSTABLE` récurrent). L'analyse (0 alerte) reste active.
- **Critère** : 3 exécutions CodeQL consécutives vertes sur `main` OU `continue-on-error` documenté ; plus de `mergeStateStatus: UNSTABLE` dû à CodeQL.
