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

### QUAL-2 — Couverture pcov + CI (Must)
| ID | Tâche | Estimation |
|----|-------|------------|
| T-QUAL-2-01 | [OPS] pcov dans l'image Docker + cible `make coverage` | 1h |
| T-QUAL-2-02 | [OPS] Baseline couverture + seuil bloquant CI (documenté) | 1.5h |

### US-074 — Export comptable FEC (Must, 8 pts)
| ID | Tâche | Estimation |
|----|-------|------------|
| T-074-01 | [DOC] ADR léger « périmètre export FEC » (écritures équilibrées via mapping) | 1h |
| T-074-02 | [DB] Entité `AccountMapping` (comptes tenant : produit/tiers/charge/contrepartie) + port | 3h |
| T-074-03 | [DB] Migration `account_mapping` (RLS tenant, unique) | 1h |
| T-074-04 | [BE] `FecLine` (VO) + `FecGenerator` (domaine) — 18 champs, débit=crédit | 4h |
| T-074-05 | [BE] Use case `ExportFec` (marges période clôturée + mapping → fichier), gating HAB-1/HAB-6 | 3h |
| T-074-06 | [FE-WEB] Bouton « Export FEC » + route téléchargement gated + écran config mapping | 3h |
| T-074-07 | [TEST] Conformité 18 champs, équilibre, nommage, gating, période/mapping absents | 3h |
| T-074-08 | [REV] Revue de clôture (`symfony-reviewer`) | 1h |

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

## 🚫 Bloqué
| ID | Raison | Action |
|----|--------|--------|

## Ordre d'exécution (phases)
1. **QUAL-1** (jour 1 — dé-risque, données réelles pour valider le S9 avant d'empiler).
2. **QUAL-2** (couverture instrumentée avant d'ajouter du code US-074).
3. **US-074** : T-074-01 (ADR) → 02/03 (mapping+migration) → 04 (FecGenerator TDD) → 05 (use case) → 06 (UI) → 07 (tests) → 08 (revue).
4. **US-018** (Should) si capacité.

## Métriques
- **Tâches Must** : 13 (QUAL-1 ×3, QUAL-2 ×2, US-074 ×8) · **Should** : US-018 ×5
- **Heures Must** : ~26.5h · **Should** : ~7.5h
- **Points engagés** : US-074 = 8 (+US-018 = 3 en Should) ; QUAL-1/2 = dette (hors vélocité)
- ⚠️ Capacité réduite (fêtes) : engager Must, tirer US-018 seulement si avance
