# Tâches techniques / dette — Sprint 10

Stories de dette qualité (hors vélocité). Issues de la rétro S9 et de l'audit.

## QUAL-1 — Recette navigateur sur données peuplées (Must, **jour 1**)

| ID | Type | Tâche | Estimation | Dépend de | Statut |
|----|------|-------|------------|-----------|--------|
| T-QUAL-1-01 | [OPS] | Étendre le seed finance : projets budgétés (coût + CA cible via `revenue_budget_cents`), imputations valorisées, ≥ 1 période **clôturée** (marges figées) | 2h | - | 🔲 |
| T-QUAL-1-02 | [TEST] | Recette navigateur (Claude in Chrome) : `/valorisation`, fiche projet (onglet Suivi budgétaire), `/finance` × 3 rôles (chef de projet / dirigeant / collaborateur) + captures | 2h | T-QUAL-1-01 | 🔲 |
| T-QUAL-1-03 | [DOC] | Rapport `.recette/` (écrans, rôles, captures, findings) + backlog des findings | 1h | T-QUAL-1-02 | 🔲 |

**Total : ~5h.** DoD : gating HAB-1 vérifié sur données réelles, rapport committé.

## QUAL-2 — Instrumentation de la couverture (Must)

| ID | Type | Tâche | Estimation | Dépend de | Statut |
|----|------|-------|------------|-----------|--------|
| T-QUAL-2-01 | [OPS] | Ajouter l'extension **pcov** à l'image Docker + cible `make coverage` (rapport texte + clover) | 1h | - | 🔲 |
| T-QUAL-2-02 | [OPS] | Mesurer la baseline, poser un **seuil bloquant en CI** (calé sur le réel, documenté), vérifier l'impact temps | 1.5h | T-QUAL-2-01 | 🔲 |

**Total : ~2.5h.** DoD : `make ci` échoue sous le seuil, baseline consignée.

## Quick-wins optionnels (Could)
- Allowlist gitleaks pour les exemples de clés dans `.claude/` et docs (audit S9) — ~0.5h.
- Profilage perf `/finance` sur le seed peuplé (ENF-PERF-3), en marge de QUAL-1 — ~1h.
