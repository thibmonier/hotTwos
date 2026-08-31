# Tâches — Sprint 2 (Consolidation technique)

## Vue d'ensemble

| Élément | Titre | Points | Tâches | Heures | Statut |
|---------|-------|--------|--------|--------|--------|
| TECH-1 | Migrations Doctrine + durcissement versionné | 5 | 7 | 16h | 🔲 |
| TECH-2 | RLS runtime par requête (finition US-001) | 5 | 7 | 18h | 🔲 |
| US-006 | Worker FrankenPHP réel + état inter-requêtes sûr | 5 | 6 | 14h | 🔲 |
| US-008 | Secrets rotatifs + observabilité de base | 5 | 7 | 16h | 🔲 |
| **Total** | | **20** | **27** | **64h** | |

## Répartition par type

| Type | Tâches | Heures | % |
|------|--------|--------|---|
| [OPS] | 9 | 24h | ~38 % |
| [DB] | 4 | 13h | ~20 % |
| [BE] | 2 | 8h | ~13 % |
| [TEST] | 5 | 13h | ~20 % |
| [DOC] | 4 | 4h | ~6 % |
| [REV] | 5 | 6h (dont revue ARC-106) | ~9 % |

> Sprint à dominante **[OPS]/[DB]** (infra) — pas de couche Flutter/Twig ni de nouveau CRUD métier. Le vertical métier (saisie de temps) démarre au Sprint 3.

## Fichiers
- [TECH-1 — Migrations + durcissement versionné](./TECH-1-tasks.md)
- [TECH-2 — RLS runtime par requête](./TECH-2-tasks.md)
- [US-006 — Worker réel + état sûr](./US-006-tasks.md)
- [US-008 — Secrets + observabilité](./US-008-tasks.md)

## Ordre d'exécution recommandé
1. **Merge PR #2** (prérequis dur) → brancher sur `main`.
2. **TECH-1** (migrations) — débloque TECH-2 et solde la dette structurante.
3. **TECH-2** (RLS runtime) — après le rôle applicatif créé par migration.
4. **US-006** et **US-008** — parallélisables après le merge.

## Conventions
- **ID** : `T-<élément>-<NN>` (ex. `T-T1-03`, `T-006-02`).
- **Taille** : 0,5 h – 8 h (idéal 2–4 h).
- **Statuts** : 🔲 À faire · 🔄 En cours · 👀 Review · ✅ Done · 🚫 Bloqué.
- **DoD par tâche** : `make ci` vert, TDD, pas de dette ajoutée.
