# Task Board — Sprint 13 (Gestion budgétaire, EPIC-002)

## Légende
🔲 À faire · 🔄 En cours · 👀 Review · ✅ Done · 🚫 Bloqué

## 🔲 À Faire

### US-033 — Budget initial/avenants/courant (Must, 8 pts)
| ID | Tâche | Est. |
|----|-------|------|
| T-033-01 | [DB] Entité `BudgetAmendment` + port | 2h |
| T-033-02 | [DB] Migration + impl Doctrine | 2h |
| T-033-03 | [BE] `AddBudgetAmendment` (gated, motif, refus clôturé) | 2h |
| T-033-04 | [BE] `CurrentProjectBudget` + rebranchement 3 lecteurs | 3h |
| T-033-05 | [FE-WEB] Formulaire avenant + historique | 3h |
| T-033-06 | [TEST] Unit + Functional (INV-2/3) | 3h |
| T-033-07 | [REV] Revue | 0.5h |

### US-078 — Budget charge par profil (Must, 8 pts)
| ID | Tâche | Est. |
|----|-------|------|
| T-078-01 | [DB] Entité `LotProfileBudget` + migration | 2.5h |
| T-078-02 | [BE] `ProfileBudgetCalculator` (RateResolver) | 2.5h |
| T-078-03 | [BE] `DefineLotProfileBudget` + lecture agrégée | 2h |
| T-078-04 | [FE-WEB] Saisie par profil + équivalents € | 3h |
| T-078-05 | [TEST] Unit + Functional | 3h |
| T-078-06 | [REV] Revue | 0.5h |

### US-032 — Projets internes (Should, stretch, 5 pts)
| ID | Tâche | Est. |
|----|-------|------|
| T-032-01 | [DB] Flag `Project::internal` + migration | 1.5h |
| T-032-02 | [BE] Exclusion marge (DQL) | 1.5h |
| T-032-03 | [BE] Occupation facturable | 2h |
| T-032-04 | [FE-WEB] Marquage + distinction + occupation | 2h |
| T-032-05 | [TEST] Exclusion/occupation/gating | 2.5h |
| T-032-06 | [REV] Revue | 0.5h |

### Transverse
| ID | Tâche | Est. |
|----|-------|------|
| T-TECH-01 | [OPS] Seed enrichi (avenant + budget profil + interne) | 1h |

## 🔄 En Cours
| ID | Tâche | Démarré |
|----|-------|---------|

## 👀 En Review
| ID | Tâche | Reviewer |
|----|-------|----------|

## ✅ Terminé
| ID | Tâche | Terminé |
|----|-------|---------|
| Setup | Décomposition Sprint 13 | 2026-09-06 |

## Ordre d'exécution
1. **US-033** (avenants → budget courant, référence du suivi/atterrissage).
2. **US-078** (budget par profil, réutilise RateResolver).
3. **US-032** (projets internes, stretch si capacité).

## Métriques
- **Engagé** : Must 16 pts (US-033 + US-078). Stretch : US-032 (5). Réserve S14 : US-079.
