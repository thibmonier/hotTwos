# Tâches — Sprint 13 (Gestion budgétaire, EPIC-002)

## Vue d'ensemble

| US | Titre | Points | Tâches | Statut |
|----|-------|--------|--------|--------|
| US-033 | Budget — initial, avenants & budget courant (EF-PRJ-8) | 8 | 7 | 🔲 |
| US-078 | Budget charge par profil (EF-PRJ-9) | 8 | 6 | 🔲 |
| US-032 | Projets internes non facturables (EF-PRJ-5, stretch) | 5 | 6 | 🔲 |

**Engagé : 16 pts Must (US-033 + US-078).** US-032 en stretch. US-079 (raffinements) → S14.

## Répartition par type (mono-stack Symfony hexagonal)

| Type | Portée |
|------|--------|
| [DB] | entités `BudgetAmendment`/`LotProfileBudget` + `Project.internal`, migrations RLS, impls Doctrine |
| [BE] | use cases (`AddBudgetAmendment`, `DefineLotProfileBudget`), `CurrentProjectBudget`, `ProfileBudgetCalculator`, filtres marge/occupation |
| [FE-WEB] | fiche projet (avenants, budget par profil, marquage interne), Twig |
| [TEST] | Unit + Functional (INV-2/3, taux historisé, exclusion marge) |
| [REV] | revues de clôture |

## Fichiers
- [US-033 — Avenants & budget courant](./US-033-tasks.md)
- [US-078 — Budget charge par profil](./US-078-tasks.md)
- [US-032 — Projets internes](./US-032-tasks.md)
- [Tâches techniques](./technical-tasks.md)

## Conventions
- **ID** : T-[US]-[NN] · **Estimation** : heures (0,5-8h) · **Statuts** : 🔲 🔄 👀 ✅ 🚫
- **Ordre** : US-033 → US-078 → US-032 (stretch).
- **Réutilisation clé** : `App\Domain\Pricing\RateResolver::resolveAt` (US-078), patron `MarginDriftThreshold` (US-033), booléen `Project::internal` + filtre DQL (US-032).
