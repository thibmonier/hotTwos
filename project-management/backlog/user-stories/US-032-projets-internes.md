# US-032: Projets internes non facturables

## Métadonnées
- **ID**: US-032
- **EPIC**: EPIC-002 (Projets & delivery)
- **Sprint**: — (backlog affiné, Ready)
- **Statut**: 🟢 Ready
- **Points**: 5
- **Persona**: P2 (Marc — Chef de projet) / P3 (Sophie — Resource Manager)
- **Créé le**: 2026-09-06

## Traçabilité
- **Implémente**: EF-PRJ-5, RG-PRJ-6
- **Dépend de**: US-030 (Project), US-060 (occupation/valorisation)

## User Story

**En tant que** responsable de production (P3),
**je veux** marquer un projet comme **interne non facturable** (R&D, avant-vente, formation, congés),
**afin d'**imputer du temps hors production client sans fausser la marge, tout en le comptant dans la capacité consommée (RG-PRJ-6).

## Contexte (Conversation)

Aujourd'hui aucun concept de projet « interne » n'existe (`ContractType` = forfait/regie ; un projet
sans budget se crée mais n'est pas typé). EF-PRJ-5 (Should) exige un **type/flag « interne non
facturable »**. Conséquences (RG-PRJ-6) : ces projets sont **exclus du calcul de marge** (pas de CA
attendu) mais **inclus dans la capacité consommée** ; le **taux d'occupation facturable** se calcule
**par exclusion** de ces projets.

## Critères d'Acceptance (Confirmation)

### CA-1 (Nominal) : créer un projet interne
```gherkin
GIVEN je crée un projet « R&D moteur IA » marqué « interne non facturable »
WHEN le projet est créé
THEN il n'exige pas de CA cible ni de client facturable
  AND l'imputation de temps y est possible
```

### CA-2 (Exclusion marge) : RG-PRJ-6
```gherkin
GIVEN des temps valorisés imputés sur un projet interne
WHEN la marge tenant/projet est calculée
THEN ce projet est exclu du calcul de marge (aucun CA reconnu attendu)
```

### CA-3 (Occupation facturable) : par exclusion
```gherkin
GIVEN un collaborateur ayant imputé 3 j sur un projet client et 2 j sur un projet interne (5 j ouvrés)
WHEN son occupation facturable est calculée
THEN elle vaut 3/5 = 60 % (les jours internes ne comptent pas comme facturables)
  AND la capacité consommée totale reste 5 j (RG-PRJ-6)
```

### CA-4 (Distinction visuelle) : pilotage
```gherkin
GIVEN la liste des projets
WHEN je la consulte
THEN les projets internes sont distingués des projets facturables
```

### CA-5 (Sécurité)
```gherkin
GIVEN un utilisateur sans CREATE_PROJECT / EDIT_PROJECT
WHEN il tente de créer/marquer un projet interne
THEN l'accès est refusé (403)
```

## Definition of Done
- [ ] Marqueur « interne non facturable » sur `Project` (type ou flag) + migration
- [ ] Exclusion des projets internes du calcul de marge (`ComputeProjectMargins` / dashboard)
- [ ] Occupation **facturable** = jours facturables / base, projets internes exclus du numérateur mais présents dans la capacité consommée (RG-PRJ-6)
- [ ] UI : création/marquage + distinction dans la liste projets
- [ ] Tests : exclusion marge, occupation facturable, capacité consommée inchangée, gating
- [ ] `make ci` vert · revue de clôture

## Notes
Attention à l'occupation existante (US-060 : jours valorisés / (ouvrés − absences)) — introduire la
notion « facturable » sans casser la définition actuelle (ajouter une mesure, ne pas remplacer).
