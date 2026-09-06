# US-078: Budget charge par profil

## Métadonnées
- **ID**: US-078
- **EPIC**: EPIC-002 (Projets & delivery)
- **Sprint**: — (backlog affiné, Ready)
- **Statut**: 🟢 Ready
- **Points**: 8
- **Persona**: P2 (Marc — Chef de projet)
- **Créé le**: 2026-09-06

## Traçabilité
- **Implémente**: EF-PRJ-9
- **Dépend de**: US-031 (lots), EPIC-001 (référentiel profils & taux de vente/coût historisés)

## User Story

**En tant que** chef de projet (P2),
**je veux** exprimer le budget de charge **par profil** (ex. 40 j senior + 20 j junior),
**afin de** connaître l'équivalent en **€ de vente** et en **€ de coût** via les taux en vigueur (EF-PRJ-9).

## Contexte (Conversation)

Aujourd'hui la charge budgétée d'un lot est un **total de jours** (`ProjectLot.budgetDays`) + un montant
global (`budgetCents`). EF-PRJ-9 (Must) exige une ventilation **par profil**, chaque ligne liant un nombre
de jours à un profil du référentiel (EPIC-001) ; les taux de vente/coût en vigueur donnent l'équivalent
en € de vente (CA cible) et en € de coût (budget de charge). Les taux sont **historisés** (EPIC-001) :
le calcul utilise le taux à la date de référence du budget.

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : budget par profil → € vente & € coût
```gherkin
GIVEN un lot et un référentiel de profils avec taux (senior : vente 800 €/j, coût 500 €/j ; junior : vente 500 €/j, coût 300 €/j)
WHEN je budgète 40 j senior + 20 j junior sur ce lot
THEN le budget de charge affiché est 60 j
  AND l'équivalent € de vente est 42 000 € (40×800 + 20×500)
  AND l'équivalent € de coût est 26 000 € (40×500 + 20×300)
```

### CA-2 (Réconciliation) : cohérence lot/projet
```gherkin
GIVEN les budgets par profil des lots d'un projet
WHEN je consulte la synthèse budget du projet
THEN les totaux jours / € vente / € coût sont la somme des lots, réconciliés avec le budget projet (écart signalé, cf. EF-PRJ-2)
```

### CA-3 (Taux historisé) : date de référence
```gherkin
GIVEN un profil dont le taux a changé au 01/06
WHEN je budgète avec une date de référence antérieure au 01/06
THEN l'équivalent € utilise le taux en vigueur à cette date (pas le taux courant)
```

### CA-4 (Profil inconnu) : robustesse
```gherkin
GIVEN une ligne de budget référençant un profil sans taux défini
WHEN je consulte l'équivalent €
THEN la ligne est signalée « taux manquant » et n'est pas comptée à tort comme 0
```

### CA-5 (Sécurité)
```gherkin
GIVEN un utilisateur sans EDIT_PROJECT
WHEN il tente de définir un budget par profil
THEN l'accès est refusé (403)
```

## Definition of Done
- [x] Modèle `LotProfileBudget` (lot → {profil, jours}) + port + migration + RLS
- [x] Conversion jours→€ vente/coût via `RateResolver::resolveAt` (taux **historisés** EPIC-001), date de référence = début projet
- [x] `ProfileBudgetCalculator` : agrégation par lot, profil sans taux signalé (CA-4)
- [x] UI : saisie du budget par profil sur le lot (onglet Structure) + affichage équivalents € vente/coût
- [x] Use case `DefineLotProfileBudget` (gated EDIT_PROJECT, upsert par couple lot/profil)
- [x] Tests : conversion multi-profils, taux historisé à date, profil sans taux, gating, functional
- [x] `make ci` vert · revue de clôture

## Note de réalisation
Réutilise intégralement `App\Domain\Pricing` (`RateResolver`, `ProfileRate` coût+vente historisés). Le budget
par profil **coexiste** avec `ProjectLot.budgetDays/budgetCents` (migration douce). Saisie au niveau lot racine.

## Notes
Réutiliser le référentiel profils/taux d'EPIC-001 (ne pas dupliquer les taux). Compatibilité ascendante :
un lot sans ventilation par profil conserve son `budgetDays`/`budgetCents` global (migration douce).
