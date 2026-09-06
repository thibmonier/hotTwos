# Tâches — Sprint 12 (Pilotage projet, EPIC-002)

## Vue d'ensemble

| US / Item | Titre | Points | Tâches | Statut |
|-----------|-------|--------|--------|--------|
| US-035 | Avancement physique & RAF par lot | 8 | 6 | 🔲 |
| US-036 | Atterrissage charge & alerte de dérive précoce | 8 | 6 | 🔲 |
| Dette | Libellés / T-R01 / MAILER | ~4 | 3 | 🔲 |

**Total : ~20 pts** (Must 16 + Should dette ~4).

## Répartition par type

| Type | Tâches (indicatif) |
|------|--------------------|
| [DB] | ProjectLot champs + migration |
| [BE] | RecordLotProgress, ChargeLandingCalculator, agrégation, ViewProjectBudgetTracking |
| [FE-WEB] | onglets Structure/Suivi budgétaire, /finance, libellés, tabs_controller |
| [TEST] | unit domaine/application + functional |
| [OPS] | MAILER_DSN staging |
| [REV] | revues de clôture |

## Fichiers
- [US-035 — Avancement & RAF](./US-035-tasks.md)
- [US-036 — Atterrissage & dérive charge](./US-036-tasks.md)
- [Tâches techniques / dette](./technical-tasks.md)

## Conventions
- **ID tâche** : T-[US]-[NN] (ex. T-035-01)
- **Estimation** : heures (0,5h – 8h)
- **Statuts** : 🔲 À faire · 🔄 En cours · 👀 Review · ✅ Done · 🚫 Bloqué
- **Ordre d'exécution** : US-035 → US-036 (dépendance avancement) ; dette en intercalaire.
- **Stack** : mono-stack Symfony hexagonal (Domain/Application/Infrastructure/UI + Twig + PHPUnit). Pas de Flutter/API Platform mobile.
