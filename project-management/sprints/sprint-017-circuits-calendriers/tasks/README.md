# Tâches - Sprint 017 : Circuits, calendriers différenciés & fermeture (EPIC-001)

## Vue d'ensemble

| US | Titre | Points | Tâches | Heures | Ordre | Statut |
|----|-------|--------|--------|--------|-------|--------|
| US-022 | Périodes de fermeture entreprise | 5 | 7 | ~14h | 1 | 🔲 |
| US-021 | Calendriers de travail différenciés (temps partiel) | 8 | 8 | ~18.5h | 2 | 🔲 |
| US-017 | Circuit de validation des absences paramétrable | 8 | 8 | ~18.5h | 3 | 🔲 |
| — | Tâches techniques transverses | — | 3 | ~2.75h | — | 🔲 |

**Total** : 26 tâches | ~53.75h | **21 points engagés** (capacité 22)

## Répartition par type
| Type | Tâches | Heures (~) |
|------|--------|-----------|
| [DB] | 6 | 11.5h |
| [INFRA] | 3 | 4.5h |
| [BE] | 5 | 13h |
| [FE-WEB] | 3 | 7.5h |
| [TEST] | 3 | 10h |
| [OPS]/[DOC]/[REV] | 6+ | 7.25h |

*(Pas de [FE-MOB] ni API Platform : server-rendered Symfony/Twig + config.)*

## Ordre d'exécution
**US-022 → US-021 → US-017** (US-022 & US-021 enrichissent `WorkingDaysCalculator` ; US-017 indépendante).

## Invariants DoD
- Migration + RLS par nouvelle table ; UI ⇏ Infra (Deptrac) ; TDD ; `make ci` vert ; couv. ≥ 80 %.
- US-021 : **`WorkingDaysCalculator::isWorkingDay(tenant,day)` inchangé** (variantes `…ForUser` ajoutées) ; ScheduleReminders non impacté.
- US-017 : circuit ≤ 2 étapes, validateur par rôle ; flux mono-étape par défaut préservé.

## Fichiers
- [US-022](./US-022-tasks.md) · [US-021](./US-021-tasks.md) · [US-017](./US-017-tasks.md) · [Tâches techniques](./technical-tasks.md)

## Conventions
- **ID** : T-[US]-[NN] · **Taille** : 0.25h–3.5h · **Statuts** : 🔲 🔄 👀 ✅ 🚫
