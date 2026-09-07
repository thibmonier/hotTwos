# Tâches - US-041 : Plan de charge — capacité vs charge ferme

## Informations US
- **Epic** : EPIC-004 · **Persona** : P3 (Resource Manager) · **Points** : 5 · **Sprint** : sprint-018 · **Ordre** : #3

## Résumé
Consolider capacité (jours ouvrés nets) vs charge ferme (affectations) par collaborateur ; détecter sur/sous-charge.

## Vue d'ensemble des tâches

| ID | Type | Tâche | Est. | Dépend | Statut |
|----|------|-------|------|--------|--------|
| T-041-01 | [DB] | `ProjectAssignmentRepository::plannedDaysByUser(tenant, from, to)` (Doctrine + fake) | 2h | - | 🔲 |
| T-041-02 | [BE] | Read model `Application\Staffing\ViewWorkloadPlan` + `WorkloadPlanView`/lignes (capacité, charge ferme, dispo, surcharge) | 3h | 01 | 🔲 |
| T-041-03 | [FE-WEB] | Controller `/planification/charge` + Twig (gating, HAB-1 : pas de coût) | 2.5h | 02 | 🔲 |
| T-041-04 | [TEST] | Unit (capacité/charge/surcharge, temps partiel) + Functional (rendu, 403, HAB-1) | 3h | 02,03 | 🔲 |
| T-041-05 | [DOC]+[REV] | PHPDoc + review + `make ci` | 1h | 04 | 🔲 |

**Total estimé** : ~11.5h

## Détails clés
- **T-041-01** : somme des `plannedDays` des `ProjectAssignment` chevauchant [from,to] par userId. Ajouter la méthode au port + Doctrine + `InMemoryProjectAssignmentRepository`.
- **T-041-02** : capacité = `WorkingDaysCalculator::workingDaysForUser` − absences validées (réutiliser la logique d'`OccupationReport`) ; charge ferme = T-041-01 ; `disponible = max(0, capacité − chargeFerme)` ; `surcharge = chargeFerme > capacité`. **Pas de charge probable** (champ non alimenté, INV-5).
- **T-041-03** : gating = permission de planification (décision : réutiliser `VIEW_PROJECT` + rôle RM, ou nouvelle perm — trancher au dev) ; aucun coût affiché (HAB-1).
- **T-041-04** : SchemaTool via trait QUAL-3.

## Résumé
| Couche | Tâches | Heures |
|--------|--------|--------|
| [DB] | 1 | 2h |
| [BE] | 1 | 3h |
| [FE-WEB] | 1 | 2.5h |
| [TEST] | 1 | 3h |
| [DOC]/[REV] | 1 | 1h |
| **TOTAL** | **5** | **~11.5h** |
