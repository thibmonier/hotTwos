# Task Board - Sprint 015 : Finition pilotage projet

## Légende
- 🔲 À faire · 🔄 En cours · 👀 En review · ✅ Terminé · 🚫 Bloqué

## 🔲 À Faire

| ID | US | Tâche | Estimation |
|----|-----|-------|------------|
| T-TECH-01 | — | [OPS] Créer la branche avant de coder | 0.25h |
| T-079c-01 | US-079c | [DB] Entité `ChargeLandingSnapshot` + port repository | 2h |
| T-079c-02 | US-079c | [DB] Migration + policy RLS | 1.5h |
| T-079c-03 | US-079c | [BE] Service de capture idempotent | 3h |
| T-079c-04 | US-079c | [BE] Handler `CaptureChargeLandingOnPeriodClosed` | 1.5h |
| T-079c-05 | US-079c | [BE] Query `ViewChargeLandingCurve` | 2h |
| T-079c-06 | US-079c | [FE-WEB] Intégration `ProjectPageController` + endpoint | 1.5h |
| T-079c-07 | US-079c | [FE-WEB] Vue Twig courbe (accessible) | 3h |
| T-079c-08 | US-079c | [TEST] Unit idempotence + preuve invariance | 2h |
| T-079c-09 | US-079c | [TEST] Functional handler + re-clôture + rendu | 3h |
| T-079c-10 | US-079c | [DOC] PHPDoc + note décision | 0.5h |
| T-079b-01 | US-079b | [DB] Entité `ChargeDriftThreshold` + port repository | 2h |
| T-079b-02 | US-079b | [DB] Migration + policy RLS | 1.5h |
| T-079b-03 | US-079b | [BE] Port `ChargeDriftThresholdProvider` (repli OBJ-2) | 2h |
| T-079b-04 | US-079b | [BE] Étendre `ChargeLandingCalculator` (seuils + escalade) | 3h |
| T-079b-05 | US-079b | [INFRA] Repository Doctrine + providers | 2h |
| T-079b-06 | US-079b | [BE] Câbler `ViewProjectBudgetTracking` | 2h |
| T-079b-07 | US-079b | [FE-WEB] Page config `/finance/config-derive-charge` | 2.5h |
| T-079b-08 | US-079b | [FE-WEB] Twig config + badge escalade | 2h |
| T-079b-09 | US-079b | [TEST] Unit calculator (type, escalade, repli) | 2h |
| T-079b-10 | US-079b | [TEST] Functional config + pilotage | 3h |
| T-079b-11 | US-079b | [DOC] PHPDoc + note EF-PRJ-15 | 0.5h |
| T-TECH-02 | — | [REV] Revue de clôture EPIC-002 + `make ci` vert | 2h |

## 🔄 En Cours
| ID | US | Tâche | Démarré |
|----|-----|-------|---------|

## 👀 En Review
| ID | US | Tâche | Reviewer |
|----|-----|-------|----------|

## ✅ Terminé
| ID | US | Tâche | Réel | Terminé |
|----|-----|-------|------|---------|

## 🚫 Bloqué
| ID | US | Raison | Action |
|----|-----|--------|--------|

## Métriques (clôture)
- **Stories** : 2/2 livrées ✅ (US-079c #82, US-079b #83)
- **Points** : 10/10 livrés — **EPIC-002 bouclé à 100 %**
- **Tests** : 628 → **645** verts | `make ci` vert à chaque merge
- **Migrations** : `charge_landing_snapshot`, `charge_drift_threshold` (+ RLS)
