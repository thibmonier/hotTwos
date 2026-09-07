# US-041: Plan de charge — capacité vs charge ferme

## Métadonnées
- **ID**: US-041
- **EPIC**: EPIC-004 (Planification & Staffing)
- **Sprint**: 18
- **Statut**: 🟢 Ready
- **Points**: 5
- **Persona**: P3 (Resource Manager)
- **Créé le**: 2026-09-07
- **Mis à jour**: 2026-09-07 (affinage S18)

## Traçabilité
- **Implémente**: EPIC-004 (OBJ-4) ; INV-5 (charge ferme ≠ probable — **part ferme uniquement**)
- **Dépend de**: US-021/022 (capacité par utilisateur), US-037 (affectations `ProjectAssignment`)
- **Reporté**: **charge probable** (pipeline pondéré) → dépend d'EPIC-006 (CRM). INV-5 respecté : la charge probable sera un champ distinct, non alimenté ici.

## User Story

**En tant que** resource manager,
**je veux** visualiser, par collaborateur et sur une période, la **capacité** (jours ouvrés nets d'absences)
et la **charge ferme** (jours affectés),
**afin de** repérer les sur/sous-charges sans tableur.

## Contexte (Conversation)
La capacité par collaborateur existe (US-021/022 : `WorkingDaysCalculator::workingDaysForUser` − absences).
Les affectations fermes existent (`ProjectAssignment` : `plannedDays`, période). Cette US **consolide**
capacité vs charge ferme sur un écran, avec détection de sur/sous-charge. La **charge probable** (CRM)
est hors périmètre.

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : capacité et charge ferme par collaborateur sur une période
```gherkin
GIVEN un collaborateur avec une capacité de 20 jours ouvrés nets sur le mois
  AND des affectations fermes totalisant 15 jours sur ce mois
WHEN le resource manager consulte le plan de charge du mois
THEN la ligne du collaborateur affiche capacité = 20, charge ferme = 15, disponible = 5
```

### CA-2 (Nominal) : détection de surcharge
```gherkin
GIVEN un collaborateur avec 18 jours de capacité et 22 jours de charge ferme
WHEN le plan de charge est calculé
THEN le collaborateur est signalé en surcharge (charge ferme > capacité)
```

### CA-3 (Alternatif) : la capacité tient compte du temps partiel et des absences
```gherkin
GIVEN un collaborateur à temps partiel (US-021) avec des absences validées
WHEN sa capacité est calculée
THEN elle reflète son régime (jours travaillés) net des fériés/fermetures et des absences
```

### CA-4 (Alternatif) : la charge probable n'est pas affichée (hors périmètre)
```gherkin
GIVEN le module CRM (charge probable) n'est pas disponible
WHEN le plan de charge est affiché
THEN seule la charge ferme est présentée ; aucune charge probable n'est calculée
```

### CA-5 (Erreur) : les coûts ne sont pas visibles dans le plan de charge (HAB-1)
```gherkin
GIVEN un resource manager sans VIEW_COLLABORATOR_COST
WHEN il consulte le plan de charge
THEN aucun coût unitaire n'est affiché
```

### CA-6 (Erreur) : accès réservé
```gherkin
GIVEN un utilisateur sans habilitation de consultation planification
WHEN il tente d'accéder au plan de charge
THEN l'accès est refusé (403)
```

## Notes techniques (pour la décomposition)
- **Charge ferme** : somme des `plannedDays` des `ProjectAssignment` du collaborateur chevauchant la période.
  → ajouter au port `ProjectAssignmentRepository` une méthode `plannedDaysByUser(tenant, from, to): array<string,int>`
  (ou `findForUser`). Doctrine + fake in-memory.
- **Capacité** : réutiliser `WorkingDaysCalculator::workingDaysForUser` − absences validées (comme `OccupationReport`).
- **Read model** : `Application\Staffing\ViewWorkloadPlan` → `WorkloadPlanView` (lignes : userId, capacité, chargeFerme, disponible, surcharge bool). Gating HAB-1 (pas de coût).
- **UI** : page `/planification/charge` (Twig) ; gating (permission de planification — cf. US-040/DoR).
- **Perf** : viser l'agrégat (pas de N+1 par jour). ENF-PERF-4 non ciblé à ce stade (dataset test).
- **Tests** : unit (calcul capacité/charge/surcharge, temps partiel) ; fonctionnels (rendu, 403, HAB-1) ; SchemaTool.

## Definition of Ready
- [x] Gherkin (2 nominaux + 2 alternatifs + 2 erreurs)
- [x] Charge ferme dérivée des affectations existantes ; charge probable reportée (INV-5)
- [x] Estimation 5 pts ; RLS/gating/HAB-1 explicités

## Definition of Done
- [ ] CA validés (unit + fonctionnels) ; `make ci` vert ; migration si nouvelle table ; code review
