# Tâches — Sprint 23 (Ouverture reskin EPIC-002 + enabler a11y)

> Décomposition Sprint Planning Part 2 (« le Comment »). Stack **mono-Symfony** (pas de Flutter/API-Platform CRUD ici) : types utilisés `[FE-WEB]`, `[BE]`, `[TEST]`, `[REV]`, `[OPS]`.

## Vue d'ensemble

| US | Titre | Points | Tâches | Heures | Statut |
|----|-------|--------|--------|--------|--------|
| US-096 | Composant `Ui:Button` accessible (focus) — bundle + adoption | 3 | 5 | 6h | 🔲 |
| US-097 | PG-PRJ-01 — reskin liste des projets | 2 | 4 | 6h | 🔲 |
| US-098 | PG-PRJ-02 — reskin fiche projet (onglets + cycle de vie) | 3 | 4 | 8.5h | 🔲 |
| US-099 | DSH-PRJ — dashboard projets (build) | 5 | 5 | 9.5h | 🔲 |
| — | Chantiers techniques (rétro S22) | — | 2 | 2.5h | 🔲 |

**Total : 20 tâches | ~32.5h** (13 pts + chantiers hors points).

## Répartition par type

| Type | Tâches | Heures |
|------|--------|--------|
| [FE-WEB] | 9 | 18.5h |
| [BE] | 1 | 3h |
| [TEST] | 5 | 8h |
| [REV] | 3 | 1.5h |
| [OPS] | 2 | 1.5h |

## Fichiers
- [US-096 — Ui:Button accessible](./US-096-tasks.md)
- [US-097 — reskin liste projets](./US-097-tasks.md)
- [US-098 — reskin fiche projet](./US-098-tasks.md)
- [US-099 — dashboard projets](./US-099-tasks.md)
- [Tâches techniques transverses](./technical-tasks.md)

## Séquencement
`T-TECH-01` (hook cs) → **US-096** (enabler bundle, en tête) → **US-097** → **US-098** → **US-099** (Should, repli) → `T-TECH-02` (CodeQL).

## Conventions
- ID : `T-<US>-NN` · estimation en heures (0.5–8h) · statuts 🔲 🔄 👀 ✅ 🚫.
- Chaque US : branche `feature/us-XXX-…`, TDD/gates, PR, CI, merge `--squash` (discipline push explicite — piège RTK).
