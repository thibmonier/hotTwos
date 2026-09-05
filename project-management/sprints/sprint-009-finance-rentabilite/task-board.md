# Task Board — Sprint 9 (Finance & rentabilité, EPIC-005)

## Légende
🔲 À faire · 🔄 En cours · 👀 En review · ✅ Terminé · 🚫 Bloqué

## 🔲 À Faire

| ID | US | Tâche | Estimation |
|----|-----|-------|------------|
| T-072-01 | US-072 | [BE] `BudgetVsActual` (budget US-033 vs réalisé) | 3h |
| T-072-02 | US-072 | [BE] Détection de dérive (seuil paramétrable) | 2h |
| T-072-03 | US-072 | [FE-WEB] Suivi budgétaire fiche projet | 3h |
| T-072-04 | US-072 | [TEST] Comparaison, alerte seuil, sans budget | 2h |
| T-072-05 | US-072 | [REV] Revue de clôture | 1h |
| T-073-01 | US-073 | [BE] Read model consolidé tenant + par projet | 3h |
| T-073-02 | US-073 | [BE] Ventilation par client (US-014) | 3h |
| T-073-03 | US-073 | [FE-WEB] Controller + route `/finance` (habilitation) | 2h |
| T-073-04 | US-073 | [FE-WEB] Template dashboard consolidé (gating, a11y) | 4h |
| T-073-05 | US-073 | [BE/TEST] Perf < 3 s (agrégats + index T-060-09) | 2h |
| T-073-06 | US-073 | [TEST] Accès 403, gating, consolidation | 3h |
| T-073-07 | US-073 | [REV] Revue de clôture | 1h |
| T-TECH-01 | — | [OPS] Recette navigateur données peuplées (rétro) | 3h |
| T-TECH-02 | — | [OPS] MAILER staging + e2e reset | 2h |
| T-TECH-03 | — | [OPS] Warmup cache dev après cache:clear | 1h |

### Réserve (Could — si capacité)
| ID | US | Tâche | Estimation |
|----|-----|-------|------------|
| T-074-01→05 | US-074 | Export comptable configurable (5 tâches) | 9.5h |

## 🔄 En Cours
| ID | US | Tâche | Démarré |
|----|-----|-------|---------|

## 👀 En Review
| ID | US | Tâche | Reviewer |
|----|-----|-------|----------|

## ✅ Terminé
| ID | US | Tâche | Terminé |
|----|-----|-------|---------|
| T-071-09 | US-071 | [REV] Revue de clôture (approuvée + réserves traitées) | 2026-09-04 |
| T-071-08 | US-071 | [DOC] ADR-0020 « facturable = CA reconnu » | 2026-09-04 |
| T-071-01 | US-071 | [DB] Entité `ProjectMargin` + port repo | 2026-09-04 |
| T-071-02 | US-071 | [DB] Migration `project_margin` (RLS, unique) | 2026-09-04 |
| T-071-03 | US-071 | [BE] `MarginCalculator` (domaine) | 2026-09-04 |
| T-071-04 | US-071 | [BE] Use case `ComputeProjectMargins` | 2026-09-04 |
| T-071-05 | US-071 | [BE] Branchement clôture (`FreezeProjectMarginsOnPeriodClosed`) | 2026-09-04 |
| T-071-06 | US-071 | [BE] Lecture marges figées + gating HAB-1 (`ViewProjectMargins`) | 2026-09-04 |
| T-071-07 | US-071 | [TEST] Tests (marge, non-rétroactif, partiel, gating) | 2026-09-04 |

## 🚫 Bloqué
| ID | US | Raison | Action |
|----|-----|--------|--------|

## Métriques
- **Tâches** : 24 engagées (+5 réserve) · 9 terminées (US-071 complète, 38 %)
- **Heures** : 55h engagées (+9.5h réserve) · ~20h consommées (US-071)
- **Points** : 21 engagés (US-071/072/073) — **8 livrés (US-071)** ; US-074 (5) en réserve
- **Décision d'entrée** : proxy « facturable = CA reconnu » (ADR léger T-071-08 en préambule)
- **Priorité qualité** : T-TECH-01 (recette données peuplées) — action rétro reconduite depuis S7
