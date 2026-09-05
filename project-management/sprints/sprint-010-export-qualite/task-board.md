# Task Board — Sprint 10 (Export comptable FEC & consolidation qualité)

## Légende
🔲 À faire · 🔄 En cours · 👀 En review · ✅ Terminé · 🚫 Bloqué

## 🔲 À Faire

_Aucune — tous les items engagés (Must + Should) sont livrés._

### Backlog issu du sprint
| ID | Origine | Description |
|----|---------|-------------|
| R-01 | recette QUAL-1 | 1er clic sur l'onglet « Suivi budgétaire » ne bascule pas le panneau (2e clic OK) — contrôleur Stimulus `tabs` | 
| US-036 | Could (non pris) | Atterrissage & détection de dérive (charge) |

## 🔄 En Cours
| ID | Tâche | Démarré |
|----|-------|---------|

## 👀 En Review
| ID | Tâche | Reviewer |
|----|-------|----------|

## ✅ Terminé
| ID | Tâche | Terminé |
|----|-------|---------|
| QUAL-2 | Couverture pcov + gate CI ≥ 80 % (baseline 82,78 %) — PR #48 | 2026-09-05 |
| US-074 | Export comptable FEC (8 pts) — PR #49, revue approuvée | 2026-09-05 |
| US-018 | Seuil de dérive paramétrable tenant (3 pts) — PR #51 | 2026-09-05 |
| QUAL-1 | Seed finance peuplé + recette navigateur (rapport `.recette/`) — PR #52 | 2026-09-05 |

## 🚫 Bloqué
| ID | Raison | Action |
|----|--------|--------|

## Ordre d'exécution (phases)
1. **QUAL-1** (jour 1 — dé-risque, données réelles pour valider le S9 avant d'empiler).
2. **QUAL-2** (couverture instrumentée avant d'ajouter du code US-074).
3. **US-074** : T-074-01 (ADR) → 02/03 (mapping+migration) → 04 (FecGenerator TDD) → 05 (use case) → 06 (UI) → 07 (tests) → 08 (revue).
4. **US-018** (Should) si capacité.

## Métriques
- **Avancement** : ✅ **100 %** — QUAL-2, US-074, US-018, QUAL-1 tous livrés et mergés (#48, #49, #51, #52).
- **Points** : **11/11 livrés** (US-074 = 8 + US-018 = 3) ; QUAL-1/2 = dette qualité soldée.
- **Dette rétro** : recette sur données peuplées (report S7→S9) **enfin soldée** (`.recette/sprint-010/`).
- **Findings** : R-01 (UX mineur, onglet) → backlog.
