# Task Board — Sprint 2 (Consolidation technique)

## Légende
🔲 À faire · 🔄 En cours · 👀 Review · ✅ Done · 🚫 Bloqué

## 🚫 Bloqué (prérequis)
| ID | Élément | Raison | Action |
|----|---------|--------|--------|
| — | Tout le sprint | PR #2 (Sprint 1) non mergée | Merger puis brancher Sprint 2 sur `main` |

## 🔲 À Faire
| ID | Élément | Tâche | Est. |
|----|---------|-------|------|
| T-006-01 | US-006 | Activer FrankenPHP worker | 3h |
| T-006-02 | US-006 | Reset état par requête (ARC-47) | 4h |
| T-006-03 | US-006 | Test deux-requêtes même worker (RSQ-15) | 3h |
| T-006-04 | US-006 | Staging worker + smoke test | 2h |
| T-006-05 | US-006 | Doc runtime worker | 1h |
| T-006-06 | US-006 | Revue | 1h |
| T-008-01 | US-008 | Secrets hors dépôt/rotatifs (ENF-SEC-10) | 3h |
| T-008-02 | US-008 | Échec si variable obligatoire manquante | 2h |
| T-008-03 | US-008 | Métrique P95 exposée | 4h |
| T-008-04 | US-008 | Suivi d'erreurs | 3h |
| T-008-05 | US-008 | Garde anti-données-réelles (ADR-13) | 2h |
| T-008-06 | US-008 | Runbook staging | 1h |
| T-008-07 | US-008 | Revue (devops) | 1h |

## 🔄 En Cours
| ID | Élément | Tâche | Démarré |
|----|---------|-------|---------|

## 👀 En Review
| ID | Élément | Tâche | Reviewer |
|----|---------|-------|----------|

## ✅ Terminé
| ID | Élément | Résultat | Commit |
|----|---------|----------|--------|
| TECH-1 (7 tâches) | Migrations Doctrine | 4 migrations (schéma + durcissement versionné), étape CI, ADR-0017 ; schema:validate in sync | 97d8a6a |
| TECH-2 (7 tâches) | RLS runtime | Rôle `hotones_app` + `TenantSessionConfigurator` (SET/RESET worker-safe) ; RLS runtime prouvée sous rôle non-superutilisateur | 5215e04 |

## Métriques
- **Tâches** : 27 total · 14 terminées (~52 %)
- **Points** : 10 / 20 livrés (TECH-1 + TECH-2)
- **Reste** : US-006 (worker réel) · US-008 (secrets + observabilité) — fortement liées au déploiement
