# US-021: Calendriers de travail différenciés

## Métadonnées
- **ID**: US-021
- **EPIC**: EPIC-001
- **Sprint**: 17
- **Statut**: 🔵 Backlog (à affiner)
- **Points**: 8 (estimation)
- **Persona**: P-ADMIN (administrateur tenant)
- **Créé le**: 2026-09-07

## Traçabilité
- **Implémente**: EF-REF-7 (calendriers différenciés : temps partiel, par entité)
- **Dépend de**: US-010 (structure organisationnelle), US-012 (jours fériés & `WorkingDaysCalculator`)
- **Réutilise / ne re-spécifie pas** : les **jours fériés** (US-012) et les **absences** (US-054).

## User Story (esquisse — à affiner)

**En tant qu'** administrateur tenant,
**je veux** définir des calendriers de travail différenciés (jours travaillés / durée par collaborateur
ou par entité, temps partiel),
**afin que** la capacité productive et l'occupation reflètent le régime réel de chaque collaborateur.

## Notes de cadrage (pour l'affinage)
- **Invasif** : introduit une **résolution de calendrier** (collaborateur > entité > tenant) consommée par
  `Domain\Calendar\WorkingDaysCalculator` (aujourd'hui : week-end + fériés tenant uniquement).
- Impact direct : `OccupationReport`, `CompletenessGrid`, `ActivitySummary` (jours ouvrés par collaborateur).
- Hors périmètre probable : forfait-jours (unité jours vs heures) — à décider à l'affinage (découpable).
- **DoR à compléter** : entité(s) calendrier + rattachement (OrgUnit/collaborateur), Gherkin, RLS, tests
  d'impact sur l'occupation, ajout aux SchemaTool.

## Definition of Ready
- [ ] Description INVEST + Gherkin (≥ 1 nominal + 2 alternatifs + 2 erreurs)
- [ ] Modèle de résolution de calendrier arrêté ; impact `WorkingDaysCalculator`/occupation cadré
- [ ] Estimation confirmée ; RLS/gating explicités
