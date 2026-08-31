# Task Board — Sprint 2 (Consolidation technique)

## Légende
🔲 À faire · 🔄 En cours · 👀 Review · ✅ Done · 🚫 Bloqué

## 🚫 Bloqué (prérequis)
| ID | Élément | Raison | Action |
|----|---------|--------|--------|
| — | Tout le sprint | PR #2 (Sprint 1) non mergée | Merger puis brancher Sprint 2 sur `main` |

## 🔲 À Faire (US-008 — observabilité/secrets, choix d'outillage avec le PO)
| ID | Élément | Tâche | Est. |
|----|---------|-------|------|
| T-008-01 | US-008 | Secrets hors dépôt/rotatifs (ENF-SEC-10) — Railway variables (partiellement acquis) | 3h |
| T-008-02 | US-008 | Échec si variable obligatoire manquante | 2h |
| T-008-03 | US-008 | Métrique P95 exposée (choix Prometheus/endpoint) | 4h |
| T-008-04 | US-008 | Suivi d'erreurs (choix Sentry vs logs structurés) | 3h |
| T-008-05 | US-008 | Garde anti-données-réelles (ADR-13) | 2h |
| T-008-06 | US-008 | Runbook staging | 1h |

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
| Bonus | Fix déprécation | `#[Autowire]` sur le canal security (ARC-51, tolérance zéro) | b725f49 |

## Métriques
- **Tâches** : 27 total · 20 terminées (~74 %)
- **Points** : 15 / 20 livrés (TECH-1, TECH-2, US-006) ; reste US-008 (observabilité/secrets)
- **Staging** : mode worker actif, migrations au déploiement, auth opérationnelle ✅
