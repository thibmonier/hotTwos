# Tâches techniques transverses — Sprint 13

## Seed & recette
| ID | Type | Tâche | Est. |
|----|------|-------|------|
| T-TECH-01 | [OPS] | Enrichir `app:demo:seed` (`SeedDemoDataCommand`) : au moins un avenant (US-033), un budget par profil (US-078) et un projet interne (US-032) — pour la recette navigateur | 1h |

## Points de vigilance (rappels)
- **Migration + `make cache-dev`** après tout `cache:clear` (faux désync `schema:validate` sinon).
- **Schémas des tests fonctionnels** : ajouter `BudgetAmendment`, `LotProfileBudget` aux `SchemaTool` des tests qui atteignent la fiche projet / le dashboard (`ProjectPageTest`, `ProjectBudgetTrackingTest`, `FinanceDashboardTest`).
- **Deptrac** : `ProfileBudgetCalculator` (Domain) dépend du port `RateResolver` (Domain/Pricing) — OK (Domain→Domain). L'UI ne référence jamais l'Infra.
- **cs-fixer / PHPStan** : appels multi-arguments un par ligne ; jamais `(string) $mixed` (garder `is_scalar`).
- **Budget courant (US-033)** : après rebranchement, vérifier la non-régression de `ProjectBudgetTrackingTest` / `FinanceDashboardTest` (le budget initial sans avenant = budget courant → valeurs inchangées).
