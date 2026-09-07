# US-022: Périodes de fermeture entreprise

## Métadonnées
- **ID**: US-022
- **EPIC**: EPIC-001
- **Sprint**: 17
- **Statut**: 🔵 Backlog (à affiner)
- **Points**: 5 (estimation)
- **Persona**: P-ADMIN (administrateur tenant)
- **Créé le**: 2026-09-07

## Traçabilité
- **Implémente**: EF-REF-9 (fermeture entreprise)
- **Dépend de**: US-012 (jours fériés & `WorkingDaysCalculator`)
- **Réutilise** : le référentiel de fériés / le calcul unifié des jours ouvrés (une fermeture = plage de jours non ouvrés à l'échelle du tenant).

## User Story (esquisse — à affiner)

**En tant qu'** administrateur tenant,
**je veux** déclarer des **périodes de fermeture** de l'entreprise (ex. entre Noël et Nouvel An),
**afin que** ces jours soient exclus des jours ouvrés (capacité/occupation) pour tous les collaborateurs.

## Notes de cadrage (pour l'affinage)
- Modèle possible : entité `ClosurePeriod` (tenant, début, fin, libellé) consommée par
  `WorkingDaysCalculator` **au même titre que les fériés** (jours non ouvrés supplémentaires).
- Priorité sur les calendriers individuels (la fermeture s'applique à tous).
- Interaction avec la saisie : blocage d'imputation productive sur les jours fermés (à confirmer).
- **DoR à compléter** : entité + RLS, Gherkin, page admin, tests d'impact occupation, SchemaTool.

## Definition of Ready
- [ ] Description INVEST + Gherkin (≥ 1 nominal + 2 alternatifs + 2 erreurs)
- [ ] Modèle `ClosurePeriod` + intégration `WorkingDaysCalculator` cadrés
- [ ] Estimation confirmée ; RLS/gating explicités
