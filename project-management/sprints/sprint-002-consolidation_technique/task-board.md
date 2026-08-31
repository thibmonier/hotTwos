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
| — | — | Tout livré | — |

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
| US-006 (6 tâches) | Worker FrankenPHP réel | Bridge worker (Symfony 8), reset d'état inter-requêtes (kernel.reset, test RSQ-15), **validé en prod** (worker + auth 401) ; migrations au démarrage du conteneur | bfac0d6, 9f0c35a, b725f49 |
| US-008 (6 tâches) | Secrets + observabilité | /metrics Prometheus (P95, **live en prod**), Sentry UE (DSN Railway), validation des variables obligatoires, garde anti-données (ADR-13), runbook | ef298fd, a5b5022 |
| Bonus | Fix déprécation | `#[Autowire]` sur le canal security (ARC-51, tolérance zéro) | b725f49 |

## Métriques
- **Tâches** : 27 total · **27 terminées (100 %)**
- **Points** : **20 / 20 livrés** (TECH-1, TECH-2, US-006, US-008)
- **Staging** : mode worker actif, migrations au déploiement, auth + /metrics opérationnels ✅
- **Réserve** : RLS runtime prête mais **inactive en prod** tant que `DATABASE_URL` n'utilise pas `hotones_app` (voir DBT-RUN-2) — l'isolation prod repose sur le filtre ORM.
