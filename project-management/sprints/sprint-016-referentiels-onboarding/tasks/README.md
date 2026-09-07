# Tâches - Sprint 016 : Référentiels de paramétrage & mise en route (EPIC-001)

## Vue d'ensemble

| US | Titre | Points | Tâches | Heures | Ordre | Statut |
|----|-------|--------|--------|--------|-------|--------|
| US-012 | Jours fériés & calcul unifié des jours ouvrés | 5 | 11 | ~22h | 1 | 🔲 |
| US-013 | Référentiel de compétences & niveaux | 3 | 9 | ~21.5h | 2 | 🔲 |
| US-020 | Journal d'audit du paramétrage | 3 | 8 | ~16h | 3 | 🔲 |
| US-019 | Onboarding tenant (défauts + checklist) | 5 | 8 | ~20.5h | 4 | 🔲 |
| — | Tâches techniques transverses | — | 4 | ~2.75h | — | 🔲 |

**Total** : 40 tâches | ~82.75h | **16 points engagés** (capacité 22)

> Les estimations en heures sont généreuses (3 nouvelles tables + refactor DRY + commande) ; le sprint
> reste piloté par les **points** (16/22). US-017 (8) en réserve si capacité.

## Répartition par type

| Type | Tâches | Heures (~) |
|------|--------|-----------|
| [DB] | 8 | 15h |
| [INFRA] | 3 | 5h |
| [BE] | 7 | 20h |
| [FE-WEB] | 6 | 16h |
| [TEST] | 7 | 17h |
| [OPS] | 2 | 2.25h |
| [DOC]/[REV] | 5+ | 7.5h |

*(Pas de [FE-MOB] ni API Platform : server-rendered Symfony/Twig + commande console.)*

## Ordre d'exécution (dépendances)
**US-012 → US-013 → US-020 → US-019** (US-020 instrumente 012/013 ; US-019 provisionne leurs défauts).

## Invariants DoD
- Migration + RLS pour chaque nouvelle table ; UI ⇏ Infra (Deptrac) ; TDD ; `make ci` vert ; couv. ≥ 80 %.
- US-012 : `WorkingDaysCalculator` unique (4 duplications supprimées).
- US-020 : journal **append-only** (INV-7), lecture **gated** (HAB-6).
- US-019 : `tenant:init` **idempotent**.

## Fichiers
- [US-012](./US-012-tasks.md) · [US-013](./US-013-tasks.md) · [US-020](./US-020-tasks.md) · [US-019](./US-019-tasks.md)
- [Tâches techniques](./technical-tasks.md)

## Conventions
- **ID** : T-[US]-[NN] · **Taille** : 0.25h–4h · **Statuts** : 🔲 🔄 👀 ✅ 🚫
