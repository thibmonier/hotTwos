# US-035: Avancement physique & RAF par lot

## Métadonnées
- **ID**: US-035
- **EPIC**: EPIC-002 (Projets & delivery)
- **Sprint**: Sprint 12 (🔴 Must)
- **Statut**: 🟢 Ready (affinée S12)
- **Points**: 8
- **Persona**: P2 (Marc — Chef de projet)
- **Créé le**: 2026-09-06

## Traçabilité
- **Implémente**: EF-PRJ-13, INV-4
- **Dépend de**: US-031 (Lots & jalons — `ProjectLot`, livré S6)
- **Prérequis de**: US-036 (l'atterrissage charge consomme l'avancement physique)

## User Story

**En tant que** chef de projet (P2),
**je veux** saisir l'**avancement physique** (%) et le **reste-à-faire (RAF)** par lot de mon projet,
**afin de** piloter la trajectoire réelle indépendamment de la consommation valorisée (INV-4).

## Contexte (Conversation)

Le grain de saisie est le **lot** (`ProjectLot`, table `project_lot`), conforme à « par phase ». Aujourd'hui
aucun avancement physique ni RAF n'est modélisé : seul l'avancement *financier* (valorisation) existe.
INV-4 impose que **avancement %, RAF (jours) et consommation (€) restent trois données distinctes**,
jamais déduites l'une de l'autre. La saisie est réservée au CP habilité (`EDIT_PROJECT`) et interdite sur
un projet clôturé (RG-PRJ-5).

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : saisie avancement + RAF
```gherkin
GIVEN un projet en cours avec un lot « Conception »
  AND je suis chef de projet habilité (EDIT_PROJECT)
WHEN je saisis un avancement de 40 % et un RAF de 6 jours sur ce lot
THEN l'avancement (40 %) et le RAF (6 j) sont persistés sur le lot
  AND ils sont distincts de la consommation valorisée du projet (INV-4)
```

### CA-2 (Alternatif) : avancement et RAF indépendants
```gherkin
GIVEN un lot sans avancement ni RAF
WHEN je saisis uniquement un avancement de 20 % (sans RAF)
THEN l'avancement est enregistré et le RAF reste vide
  AND l'inverse est également possible (RAF sans avancement)
```

### CA-3 (Alternatif) : projet clôturé en lecture seule
```gherkin
GIVEN un projet clôturé
WHEN je tente de modifier l'avancement d'un de ses lots
THEN la modification est refusée (RG-PRJ-5, projet non modifiable)
```

### CA-4 (Erreur) : avancement hors bornes
```gherkin
GIVEN un lot
WHEN je saisis un avancement de 120 % (ou -10 %)
THEN une erreur métier est levée (avancement attendu entre 0 et 100)
  AND rien n'est persisté
```

### CA-5 (Erreur) : RAF négatif
```gherkin
GIVEN un lot
WHEN je saisis un RAF de -3 jours
THEN une erreur métier est levée (RAF attendu ≥ 0)
```

### CA-6 (Erreur) : non habilité
```gherkin
GIVEN un utilisateur sans permission EDIT_PROJECT
WHEN il tente de saisir l'avancement d'un lot
THEN l'accès est refusé (403)
```

## Definition of Done
- [x] `ProjectLot` : champs `physicalProgressPercent`/`remainingWorkDays` + mutateur avec invariants (0-100, RAF ≥ 0)
- [x] `ManageProjectLots::recordProgress` gated `EDIT_PROJECT` (refus projet clôturé via `assertModifiable`)
- [x] Migration `project_lot` (colonnes nullable) + `schema:validate`
- [x] Saisie par lot dans l'onglet Structure de la fiche projet
- [x] Tests : invariants (`ProjectLotTest`), gating/refus (`RecordLotProgressTest`), INV-4 (functional `ProjectPageTest`)
- [x] `make ci` vert (couverture ≥ 80 %) · revue de clôture

## Note de réalisation
`recordProgress` a été ajouté au use case existant `ManageProjectLots` (aux côtés de `addLot`/`reallocate`)
plutôt que dans une classe dédiée — plus DRY et cohérent avec le module. Action web `project_lot_progress`
sur `ProjectStructureController` (CSRF `project_structure`).

## Notes
Réutilise `ProjectLot`/`ProjectLotRepository` (US-031) et le pattern d'action gated de `ProjectPageController`.
INV-4 : ne jamais déduire l'avancement de la consommation ni l'inverse.
