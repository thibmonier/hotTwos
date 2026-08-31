# Tâches — Sprint 0 (Fondations & Outillage)

## Vue d'ensemble

| US | Titre | Points | Tâches | Heures | Statut |
|----|-------|--------|--------|--------|--------|
| US-006 | Squelette Symfony 8 + FrankenPHP worker + architecture | 8 | 10 | 29h | 🔲 |
| US-007 | Environnement conteneurisé + données de test | 5 | 8 | 18h | 🔲 |
| US-004 | Chaîne CI/CD et exécution en mode worker | 5 | 10 | 18h | 🔲 |
| US-008 | Staging, secrets et observabilité de base | 5 | 8 | 19h | 🔲 |
| US-009 | Outillage qualité/sécurité + conventions agent | 5 | 9 | 17h | 🔲 |
| **Total** | | **28** | **45** | **101h** | |

> **Charge vs capacité.** 101h de tâches pour un sprint de 2 semaines. À ~21 j-dev d'équipe supposée (hypothèse `ARB-20`), c'est tenable ; à 1 personne (`HYP-15`), le calendrier s'étale. Sprint de fondation à forte composante setup — surveiller les composants jeunes (FrankenPHP worker, Reprise 0.x — provision ch.12 §17).

## Répartition par type (nombre de tâches)

| Type | Tâches | Commentaire |
|------|--------|-------------|
| `[OPS]` | ~25 | Docker, FrankenPHP worker, CI/CD, staging, outillage sécurité — cœur d'un Sprint 0 |
| `[BE]` | ~5 | Bootstrap Symfony, structure Clean/DDD, API Platform DTO, hooks |
| `[DB]` | 2 | PostgreSQL + pgvector, fixtures 3 tailles de tenant |
| `[FE-WEB]` | 1 | Chaîne d'assets Twig/Stimulus/Turbo + Reprise/Vite |
| `[TEST]` | ~4 | Tests worker, isolation, régénération données, RG-* nommé |
| `[DOC]` | ~6 | READMEs, runbook staging, conventions de développement versionnées |
| `[REV]` | ~2 | Revues de code |

> Pas de `[FE-MOB]`/Flutter : la stack HotOnes est Symfony full-stack (Twig/Turbo), le « mobile » est du web responsive (`ENF-UX-3`).

## Fichiers de détail

- [US-006 — Squelette applicatif](./US-006-tasks.md)
- [US-007 — Environnement conteneurisé](./US-007-tasks.md)
- [US-004 — Chaîne CI/CD mode worker](./US-004-tasks.md)
- [US-008 — Staging, secrets, observabilité](./US-008-tasks.md)
- [US-009 — Outillage qualité/sécurité](./US-009-tasks.md)
- [Tâches techniques transverses](./technical-tasks.md)
- [Task Board](../task-board.md)

## Ordre de démarrage recommandé

1. **US-006** (squelette) — prérequis de tout le reste.
2. **US-007** (conteneurs) — en parallèle dès que le squelette démarre.
3. **US-009** (outillage) et **US-004** (CI/CD) — une fois le squelette buildable.
4. **US-008** (staging) — une fois la CI en place.

## Conventions

- **ID** : `T-<US>-<NN>` (ex : `T-006-01`).
- **Taille** : 0,5h à 8h (idéal 2-4h).
- **Statuts** : 🔲 À faire · 🔄 En cours · 👀 Review · ✅ Done · 🚫 Bloqué.
- **Estimation en heures** (les points restent au niveau US).
