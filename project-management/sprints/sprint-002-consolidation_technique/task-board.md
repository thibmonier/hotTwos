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
| T-T1-01 | TECH-1 | Installer/configurer doctrine/migrations | 2h |
| T-T1-02 | TECH-1 | Migration initiale du schéma existant | 3h |
| T-T1-03 | TECH-1 | Migration durcissement RLS+trigger (idempotente) | 4h |
| T-T1-04 | TECH-1 | Tests d'intégration sur migrations + schema:validate | 3h |
| T-T1-05 | TECH-1 | Étape CI « migrations » + détection de drift | 2h |
| T-T1-06 | TECH-1 | ADR migrations + conventions | 1h |
| T-T1-07 | TECH-1 | Revue | 1h |
| T-T2-01 | TECH-2 | Rôle applicatif PG non-superutilisateur (migration) | 3h |
| T-T2-02 | TECH-2 | RLS sur toutes les tables TenantOwned (migration) | 3h |
| T-T2-03 | TECH-2 | SET app.current_tenant par requête (worker-safe) | 4h |
| T-T2-04 | TECH-2 | Test d'intrusion « RLS seule » runtime | 3h |
| T-T2-05 | TECH-2 | Tests nominaux sous rôle applicatif | 2h |
| T-T2-06 | TECH-2 | Doc sécurité double barrière | 1h |
| T-T2-07 | TECH-2 | Revue ARC-106 (security-auditor) | 2h |
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
| ID | Élément | Tâche | Terminé |
|----|---------|-------|---------|

## Métriques
- **Tâches** : 27 total · 0 terminées (0 %)
- **Heures** : 64h estimées · 0h consommées · 64h restantes
- **Points** : 20 engagés
