# Task Board — Sprint 10 (Export comptable FEC & consolidation qualité)

## Légende
🔲 À faire · 🔄 En cours · 👀 En review · ✅ Terminé · 🚫 Bloqué

## 🔲 À Faire

### QUAL-1 — Recette données peuplées (Must, **jour 1**)
| ID | Tâche | Estimation |
|----|-------|------------|
| T-QUAL-1-01 | [OPS] Étendre le seed finance (projets budgétés coût+CA cible, valorisation, période clôturée figée) | 2h |
| T-QUAL-1-02 | [TEST] Recette navigateur (`/valorisation`, fiche projet, `/finance`) × 3 rôles + captures | 2h |
| T-QUAL-1-03 | [DOC] Rapport `.recette/` + backlog des findings | 1h |


## 🔲 À Faire — Should (si capacité, fêtes)

### US-018 — Seuils paramétrables tenant (tranche dérive marge)
| ID | Tâche | Estimation |
|----|-------|------------|
| T-018-01 | [DB] Entité `MarginDriftThreshold` tenant (patron `ReminderRule`) + migration RLS | 2h |
| T-018-02 | [BE] `TenantMarginDriftThresholdProvider` (remplace Default) + config | 2h |
| T-018-03 | [FE-WEB] Paramétrage admin du seuil | 1.5h |
| T-018-04 | [TEST] Override seuil + fallback défaut | 1.5h |
| T-018-05 | [REV] Revue de clôture | 0.5h |

### Could (non affiné)
| ID | Tâche | Estimation |
|----|-------|------------|
| US-036 | Atterrissage & détection de dérive (charge) — à affiner si tout le reste est fini | ? |

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

## 🚫 Bloqué
| ID | Raison | Action |
|----|--------|--------|

## Ordre d'exécution (phases)
1. **QUAL-1** (jour 1 — dé-risque, données réelles pour valider le S9 avant d'empiler).
2. **QUAL-2** (couverture instrumentée avant d'ajouter du code US-074).
3. **US-074** : T-074-01 (ADR) → 02/03 (mapping+migration) → 04 (FecGenerator TDD) → 05 (use case) → 06 (UI) → 07 (tests) → 08 (revue).
4. **US-018** (Should) si capacité.

## Métriques
- **Avancement** : QUAL-2 ✅ + US-074 ✅ (8 pts livrés). Reste : QUAL-1 (recette navigateur, manuel) + US-018 (Should).
- **Points** : 8/8 engagés livrés (US-074) ; US-018 (3) en Should à décider ; QUAL-1/2 = dette
- ⚠️ Capacité réduite (fêtes) : Must livré ; US-018 seulement si avance ; QUAL-1 nécessite l'app + Chrome
