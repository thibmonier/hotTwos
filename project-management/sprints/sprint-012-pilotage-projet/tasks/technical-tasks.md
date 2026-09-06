# Tâches techniques / dette — Sprint 12

Dette de finition reportée, embarquée sur décision PO (rétro S11, actions 1-3).

| ID | Type | Tâche | Est. | Statut |
|----|------|-------|------|--------|
| T-DET-01 | [FE-WEB] | Harmoniser les libellés « CA reconnu » → « Revenu retenu » | 1.5h | 🔲 |
| T-R01 | [FE-WEB] | Corriger l'onglet « Suivi budgétaire » (1er clic — Stimulus `tabs`) | 1.5h | 🔲 |
| T-OPS-01 | [OPS] | `MAILER_DSN` staging | 1.5h | 🔲 |

## T-DET-01 [FE-WEB] — Libellés « Revenu retenu »
- **Raison** : depuis US-076, la valeur affichée est le **revenu retenu** (facturé réel sinon CA reconnu). Le libellé « CA reconnu » est devenu mensonger côté UI (corrigé côté FEC en S11).
- Fichiers : `templates/finance/index.html.twig` (l. 52, 94, 130), `templates/valuation/index.html.twig` (l. 34, 123).
- Ajuster les tests fonctionnels asservis à la chaîne « CA reconnu » le cas échéant.

## T-R01 [FE-WEB] — Onglet « Suivi budgétaire »
- **Raison** : reconduit S10→S11. Le 1er clic n'active pas l'onglet.
- Fichier : `assets/controllers/tabs_controller.js` — ajouter un `connect()` synchronisant l'onglet actif initial et robustesse quand des onglets sont rendus conditionnellement (`{% if canViewFinancials %}`). Vérifier la cohérence `aria-controls`/`id` dans `templates/project/show.html.twig`.

## T-OPS-01 [OPS] — `MAILER_DSN` staging
- **Raison** : reconduit S8→S11.
- `config/packages/mailer.yaml` est déjà générique (`%env(MAILER_DSN)%`). Poser la variable d'environnement `MAILER_DSN` du service `app` en **staging** vers un SMTP relais (pas de secret en dur, pas dans le dépôt). Documenter la procédure + note recette e-mail. Dev local = mailpit (compose.override).
