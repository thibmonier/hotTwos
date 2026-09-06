# Tâches techniques transverses — Sprint 14

| ID | Type | Tâche | Est. |
|----|------|-------|------|
| T-TECH-01 | [OPS] | Enrichir `app:demo:seed` : un taux de vente client/projet (US-015), une devise + taux de change (US-016), quelques snapshots d'atterrissage (US-079c) — pour la recette | 1h |

## Points de vigilance (rappels rétro S13)
- **`git switch -c` AVANT de coder** (branche oubliée en S13).
- **Ne pas placer un `<form>`/`_token` avant un formulaire lu en `.first()`** (piège CSRF `testLifecycleTransition`).
- **Schémas des tests fonctionnels** : ajouter `SellingRate`, `Currency`/`ExchangeRate`, `ChargeLandingSnapshot` aux `SchemaTool` des tests qui atteignent les pages concernées.
- **cs-fixer/rector préventifs** avant `make ci` (multi-args par ligne, `new X()->`, cast redondant, `is_scalar` avant cast mixed).
- **Migration + `make cache-dev`** après tout `cache:clear`.
- **Deptrac** : résolveurs/convertisseurs sont du Domain (dépendent d'autres ports Domain) ; l'UI ne référence jamais l'Infra.
