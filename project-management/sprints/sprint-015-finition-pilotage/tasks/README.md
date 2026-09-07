# Tâches - Sprint 015 : Finition pilotage projet (EPIC-002)

## Vue d'ensemble

Bouclage d'EPIC-002 : historisation/courbe de l'atterrissage en charge + seuil de dérive
paramétrable par type de projet (avec 2e seuil d'escalade direction).

| US | Titre | Points | Tâches | Heures | Priorité | Statut |
|----|-------|--------|--------|--------|----------|--------|
| US-079c | Courbe d'atterrissage historisée (capture légère) | ~5 | 10 | ~22h | 🔴 Must | 🔲 |
| US-079b | Seuil de dérive charge par type + 2e seuil direction | ~5 | 11 | ~24.5h | 🟡 Should | 🔲 |
| — | Tâches techniques transverses | — | 2 | ~2.25h | — | 🔲 |

**Total** : 23 tâches | ~48.75h | ~10 points engagés

## Répartition par type

| Type | Tâches | Heures | % |
|------|--------|--------|---|
| [DB] | 4 | 7h | 14% |
| [BE] | 6 | 13.5h | 28% |
| [INFRA] | 1 | 2h | 4% |
| [FE-WEB] | 4 | 9h | 18% |
| [TEST] | 4 | 10h | 21% |
| [DOC] | 2 | 1h | 2% |
| [OPS] | 1 | 0.25h | 1% |
| [REV] | 1 | 2h | 4% |

*(Pas de [FE-MOB] ni API Platform : la fonctionnalité est server-rendered Symfony/Twig.)*

## Fichiers
- [US-079c - Courbe d'atterrissage historisée](./US-079c-tasks.md)
- [US-079b - Seuil de dérive par type + escalade](./US-079b-tasks.md)
- [Tâches techniques transverses](./technical-tasks.md)

## Invariants DoD (rappel sprint-goal)
- **US-079c** : `ComputeProjectMargins` **non modifié** (capture via handler séparé) ; snapshot
  idempotent par (tenant, projet, période).
- **US-079b** : seuil par **type de projet** remplace les constantes `ChargeLandingCalculator`
  (repli = constantes OBJ-2) ; 2e seuil d'escalade direction ; gating HAB-1 préservé.
- Migration + RLS pour toute nouvelle entité ; **UI ne dépend jamais de l'Infra** (Deptrac).
- `make ci` vert (PHPStan max, Deptrac, gitleaks) ; couverture ≥ 80 %.

## Ordre d'exécution
1. **US-079c** (courbe, capture légère) → 2. **US-079b** (seuil par type).

## Conventions
- **ID** : T-[US]-[Numéro] (ex : T-079c-05)
- **Taille** : 0.25h – 3h par tâche
- **Statuts** : 🔲 À faire · 🔄 En cours · 👀 Review · ✅ Done · 🚫 Bloqué
