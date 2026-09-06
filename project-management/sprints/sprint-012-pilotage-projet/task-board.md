# Task Board — Sprint 12 (Pilotage projet, EPIC-002)

## Légende
🔲 À faire · 🔄 En cours · 👀 Review · ✅ Done · 🚫 Bloqué

## 🔲 À Faire

### US-035 — Avancement physique & RAF par lot (Must, 8 pts)
| ID | Tâche | Est. |
|----|-------|------|
| T-035-01 | [DB] Champs + mutateur `ProjectLot` | 2h |
| T-035-02 | [DB] Migration `project_lot` | 1h |
| T-035-03 | [BE] Use case `RecordLotProgress` (gated EDIT_PROJECT) | 2h |
| T-035-04 | [FE-WEB] Contrôleur + saisie onglet Structure | 3h |
| T-035-05 | [TEST] Unit + Functional (INV-4) | 3h |
| T-035-06 | [DOC/REV] Doc + revue | 1h |

### US-036 — Atterrissage charge & alerte de dérive précoce (Must, 8 pts)
| ID | Tâche | Est. |
|----|-------|------|
| T-036-01 | [BE] `ChargeLandingCalculator` + DTO | 3h |
| T-036-02 | [BE] Agrégation avancement projet | 1.5h |
| T-036-03 | [BE] Exposition `ViewProjectBudgetTracking` (HAB-1) | 2.5h |
| T-036-04 | [FE-WEB] UI atterrissage + badge + /finance | 3h |
| T-036-05 | [TEST] Unit matrice OBJ-2 + HAB-1 + Functional | 3h |
| T-036-06 | [REV] Revue | 0.5h |

### Dette (Should)
| ID | Tâche | Est. |
|----|-------|------|
| T-DET-01 | [FE-WEB] Libellés « CA reconnu » → « Revenu retenu » | 1.5h |
| T-R01 | [FE-WEB] Onglet « Suivi budgétaire » (Stimulus `tabs`) | 1.5h |
| T-OPS-01 | [OPS] `MAILER_DSN` staging | 1.5h |

## 🔄 En Cours
| ID | Tâche | Démarré |
|----|-------|---------|

## 👀 En Review
| ID | Tâche | Reviewer |
|----|-------|----------|

## ✅ Terminé
| ID | Tâche | Terminé |
|----|-------|---------|
| Setup | Réconciliation EPIC-002 + affinage US-035/036 + décomposition | 2026-09-06 |

## Ordre d'exécution
1. **US-035** (fondation avancement/RAF).
2. **US-036** (atterrissage — consomme l'avancement).
3. **Dette** (T-DET-01, T-R01, T-OPS-01) en intercalaire.

## Métriques
- **Engagé** : Must 16 pts (US-035 + US-036) + Should ~4 pts (dette) ≈ **20 pts**.
- Could hors sprint : US-033 (budget charge), US-032 (projets internes) → réserve S13.
