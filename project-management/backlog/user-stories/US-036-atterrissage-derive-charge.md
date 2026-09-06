# US-036: Atterrissage charge & alerte de dérive précoce

## Métadonnées
- **ID**: US-036
- **EPIC**: EPIC-002 (Projets & delivery)
- **Sprint**: Sprint 12 (🔴 Must)
- **Statut**: 🟢 Ready (affinée S12)
- **Points**: 8
- **Persona**: P2 (Marc — Chef de projet) / Direction
- **Créé le**: 2026-09-06

## Traçabilité
- **Implémente**: EF-PRJ-14, EF-PRJ-15, OBJ-2 (pain point n°1)
- **Dépend de**: US-035 (avancement physique), US-060 (consommation valorisée), US-072/US-018 (pattern seuil/dérive)
- **Décision**: seuils OBJ-2 en constantes de domaine (10 % / 50 %) ; configurable = suivi ultérieur.

## User Story

**En tant que** chef de projet (P2),
**je veux** connaître l'**atterrissage en charge** de mon projet et être **alerté d'une dérive dès qu'un
dépassement > 10 % est projeté avant 50 % de consommation**,
**afin de** réagir tôt (OBJ-2), au lieu de découvrir la dérive à 60-80 % du budget consommé.

## Contexte (Conversation)

Atterrissage charge (EAC, *estimate at completion*) = **coût consommé / (avancement physique % / 100)**.
Il croise trois données distinctes (INV-4) : consommation valorisée (`ProjectValuationLine::costCents` via
`projectBreakdownFor`), avancement physique (US-035, agrégé par lot pondéré `budgetDays`) et budget charge
cible (`Project::budgetCents`). L'alerte de **dérive précoce** se déclenche si le dépassement projeté
dépasse **10 %** ET que la consommation est **< 50 %** (OBJ-2). On réutilise le pattern de dérive existant
(`BudgetTrackingCalculator` calcule déjà `consumptionPercent`/`isDrifting` côté marge ; son DTO note « dérive
de charge = US-036 »). Gating HAB-1 : le CP voit l'atterrissage/dérive **globaux**, jamais un coût unitaire.

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : atterrissage charge calculé
```gherkin
GIVEN un projet avec budget charge 100 000 €, consommation valorisée 30 000 € et avancement physique 30 %
WHEN je consulte le suivi budgétaire du projet
THEN l'atterrissage charge affiché est 100 000 € (30 000 / 0,30)
  AND le dépassement projeté est 0 %
```

### CA-2 (Alerte) : dérive précoce détectée
```gherkin
GIVEN un projet avec budget charge 100 000 €, consommation 40 000 € et avancement physique 25 %
  # atterrissage = 160 000 € → dépassement +60 %, consommation 40 % (< 50 %)
WHEN je consulte le suivi budgétaire
THEN une alerte de dérive précoce est affichée (dépassement > 10 % ET consommation < 50 %)
```

### CA-3 (Pas d'alerte tardive) : consommation ≥ 50 %
```gherkin
GIVEN un projet dont la consommation atteint 70 % avec un dépassement projeté de 20 %
WHEN je consulte le suivi budgétaire
THEN l'atterrissage est affiché SANS alerte de dérive « précoce » (fenêtre OBJ-2 dépassée)
```

### CA-4 (INV-4) : trois données distinctes
```gherkin
GIVEN un projet
WHEN l'atterrissage est calculé
THEN avancement physique, RAF et consommation sont utilisés distinctement, jamais confondus ni déduits l'un de l'autre
```

### CA-5 (Erreur/robustesse) : données manquantes
```gherkin
GIVEN un projet sans budget charge OU sans avancement physique (0 %)
WHEN je consulte le suivi budgétaire
THEN l'atterrissage est « indisponible » (pas de division par zéro) et aucune alerte n'est levée
```

### CA-6 (Sécurité) : HAB-1
```gherkin
GIVEN un chef de projet (sans VIEW_COLLABORATOR_COST)
WHEN il consulte le suivi budgétaire
THEN il voit l'atterrissage/dérive globaux mais jamais le coût unitaire d'un collaborateur
```

## Definition of Done
- [x] Domaine `ChargeLandingCalculator` + DTO `ChargeLanding` (EAC, `overrunPercent`, `consumptionPercent`, `isEarlyDrift`) ; constantes OBJ-2 (10 % / 50 %)
- [x] Agrégation avancement projet `ProjectProgressCalculator` (pondérée `budgetDays`)
- [x] Application : atterrissage exposé via `ViewProjectBudgetTracking` (gating HAB-1)
- [x] UI : atterrissage + badge alerte de dérive précoce dans l'onglet « Suivi budgétaire »
- [x] Tests : matrice OBJ-2 (dépassement >/≤ 10 % × conso </≥ 50 %), cas manquants, HAB-1, INV-4
- [x] `make ci` vert (couverture ≥ 80 %) · revue de clôture

## Notes
Moteur unique (ARC-6) : réutilise `projectBreakdownFor()`, `Project::budgetCents()`, l'avancement d'US-035.
Ne pas exposer de coût unitaire (HAB-1). Atterrissage montant = tranche ultérieure (le RAF est capté par US-035 pour ce futur).

## Décisions de réalisation
- **Gating (CA-6)** : l'alerte + ratios (dépassement %, avancement %) sont visibles dès `VIEW_PROJECT_FINANCIALS` ; les **montants € d'atterrissage** et la consommation restent réservés à `VIEW_COLLABORATOR_COST`. Le coût unitaire n'est jamais montré ; un CP « financials sans coût » voit bien la dérive.
- **Compteur `/finance` différé** (hors CA) : le dashboard consolide des marges **figées par période**, alors que l'atterrissage croise un avancement **courant** → mélange sémantiquement incohérent. L'alerte vit sur la fiche projet, là où le CP agit (YAGNI ; un indicateur consolidé serait une story dédiée).
