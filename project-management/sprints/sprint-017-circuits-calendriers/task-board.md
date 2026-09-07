# Task Board - Sprint 017 : Circuits, calendriers & fermeture

## Légende
🔲 À faire · 🔄 En cours · 👀 En review · ✅ Terminé · 🚫 Bloqué

## 🔲 À Faire

### US-022 — Fermeture entreprise (ordre 1)
| ID | Tâche | Est. |
|----|-------|------|
| T-022-01 | [DB] Entité ClosurePeriod + port | 2h |
| T-022-02 | [DB] Migration + RLS | 1.5h |
| T-022-03 | [INFRA] Doctrine repo + binding | 1.5h |
| T-022-04 | [BE] WorkingDaysCalculator : fermetures | 2h |
| T-022-05 | [FE-WEB] Controller + Twig fermetures | 2.5h |
| T-022-06 | [TEST] Unit + Functional | 3h |
| T-022-07 | [DOC]+[REV] | 1.5h |

### US-021 — Calendriers différenciés (ordre 2)
| ID | Tâche | Est. |
|----|-------|------|
| T-021-01 | [DB] Entité WorkSchedule + port | 2h |
| T-021-02 | [DB] Migration + RLS | 1.5h |
| T-021-03 | [INFRA] Doctrine repo + binding | 1.5h |
| T-021-04 | [BE] Variantes ForUser du calculateur | 3h |
| T-021-05 | [BE] Basculer occupation/complétude/activité | 3h |
| T-021-06 | [FE-WEB] Controller + Twig régimes | 2.5h |
| T-021-07 | [TEST] Unit + Functional | 3.5h |
| T-021-08 | [DOC]+[REV] | 1.5h |

### US-017 — Circuit validation absences (ordre 3)
| ID | Tâche | Est. |
|----|-------|------|
| T-017-01 | [DB] Entité AbsenceValidationCircuit + port | 2.5h |
| T-017-02 | [DB] Migrations RLS + current_step | 2h |
| T-017-03 | [INFRA] Doctrine repo + binding | 1.5h |
| T-017-04 | [BE] AbsenceRequest.currentStep | 2h |
| T-017-05 | [BE] DecideAbsence étape-aware | 3h |
| T-017-06 | [FE-WEB] Controller + Twig circuits | 2.5h |
| T-017-07 | [TEST] Unit + Functional | 3.5h |
| T-017-08 | [DOC]+[REV] | 1.5h |

### Transverses
| ID | Tâche | Est. |
|----|-------|------|
| T-TECH-01 | [OPS] Branche par story | 0.25h×3 |
| T-TECH-03 | [REV] Clôture S17 + make ci | 2h |

## 🔄 En Cours / 👀 En Review / ✅ Terminé / 🚫 Bloqué
_(vide au démarrage)_

## Métriques (clôture)
- **Stories** : 3/3 livrées ✅ (US-022 #97, US-021 #98, US-017 #99)
- **Points** : 21/21 livrés — **EPIC-001 bouclé**
- **Tests** : 672 → **685** verts | `make ci` vert à chaque merge
- **Migrations** : `closure_period`, `work_schedule`, `absence_validation_circuit` (+ `current_step`) + RLS
