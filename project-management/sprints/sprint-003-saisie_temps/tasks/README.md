# Tâches — Sprint 3 (Première saisie de temps)

## Vue d'ensemble

| Élément | Titre | Points | Tâches | Heures | Statut |
|---------|-------|--------|--------|--------|--------|
| US-050 | Saisie hebdo/quotidienne (+ Projet minimal) | 5 | 7 | 21h | 🔵 |
| US-051 | Semaine ≤ 2 min (bloquant) | 8 | 6 | 19h | 🔵 |
| US-055 | Validation par lot | 5 | 6 | 18h | 🔵 |
| TECH-3 | RLS prod + smoke déploiement | 5 | 5 | 10h | 🔵 |
| **Total** | | **23** | **24** | **68h** | |

## Répartition par type

| Type | Heures (~) | % |
|------|-----------|---|
| [DB] | 9h | 13 % |
| [BE] | 24h | 35 % |
| [FE-WEB] | 19h | 28 % |
| [TEST] | 14h | 21 % |
| [DOC]/[REV]/[OPS] | (répartis) | ~ |

> Premier sprint **métier** : dominante [BE]/[FE-WEB] (saisie + validation). Pas de couche Flutter ce sprint (web d'abord — la saisie mobile est US-052, ultérieure).

## Fichiers
- [US-050 — Saisie + Projet minimal](./US-050-tasks.md)
- [US-051 — Semaine ≤ 2 min](./US-051-tasks.md)
- [US-055 — Validation par lot](./US-055-tasks.md)
- [TECH-3 — RLS prod + smoke](./TECH-3-tasks.md)

## Ordre d'exécution
1. **US-050** (entités Project + TimeEntry, saisie) — débloque US-051 et US-055.
2. **US-051** (optimisation ≤ 2 min, bloquant `RSQ-1`).
3. **US-055** (validation par lot, habilitation chef de projet).
4. **TECH-3** (RLS prod + smoke) — parallélisable.

## Conventions
- **ID** : `T-<élément>-<NN>`. **Taille** : 0,5–8 h. **Statuts** : 🔲 🔄 👀 ✅ 🚫.
- **DoD par tâche** : `make ci` vert, TDD, habilitation vérifiée côté serveur (ARC-19/106), isolation tenant.
- **INV-2** : durées en entiers (minutes), jamais de flottant sur une donnée de temps/coût.
