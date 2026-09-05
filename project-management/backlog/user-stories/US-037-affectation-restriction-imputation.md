# US-037: Affectation et restriction d'imputation

## Métadonnées
- **ID**: US-037
- **EPIC**: EPIC-002
- **Sprint**: 3
- **Statut**: ✅ Done (livré Sprint 6)
- **Points**: 5
- **Persona**: P2 (Marc – Chef de projet), P3 (Sophie – Resource manager)
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: EF-PRJ-19, EF-PRJ-20
- **Dépend de**: US-001 (socle multitenant), US-003 (socle authentification), US-010 (organisation), US-011 (profils et taux), US-030 (création projet)
- **Spec Technique**: PRJ-19 – Affectation et droits d'imputation

## User Story

**En tant que** Marc (chef de projet) ou Sophie (resource manager),
**je veux** affecter des collaborateurs à un projet avec un rôle, une période et une charge prévisionnelle, et restreindre les imputations aux seuls collaborateurs affectés,
**afin de** garantir que seules les personnes autorisées peuvent pointer sur le projet et d'alimenter le plan de charge avec des données fiables.

## Critères d'Acceptation

### CA-1 (Nominal): Collaborateur non affecté ne voit pas le projet dans la saisie de temps
```gherkin
GIVEN le projet PRJ-0042 "Refonte SI" existe avec les affectations : Marc Dupont (CP) et Julie Martin (Développeuse)
  AND le collaborateur Thomas Petit n'est pas affecté à ce projet
WHEN Thomas Petit ouvre son interface de saisie de temps pour la semaine du 2026-10-06
THEN le projet PRJ-0042 n'apparaît pas dans sa liste de projets disponibles
  AND Thomas ne peut pas saisir de temps sur PRJ-0042 par aucun moyen
```

### CA-2 (Alternatif): Ouverture exceptionnelle tracée pour un collaborateur non affecté
```gherkin
GIVEN Thomas Petit n'est pas affecté au projet PRJ-0042
WHEN Marc demande à accorder une ouverture exceptionnelle d'imputation à Thomas
     pour la semaine du 2026-10-13, motif "Renfort exceptionnel tests"
THEN l'ouverture exceptionnelle est enregistrée avec : auteur = Marc Dupont,
     date = 2026-10-10, période = semaine 42, motif = "Renfort exceptionnel tests"
  AND Thomas voit PRJ-0042 dans sa liste de saisie uniquement pour la semaine 42
  AND à l'issue de la semaine 42, l'accès est révoqué automatiquement
  AND Marc et Sophie reçoivent un récapitulatif des ouvertures exceptionnelles actives
```

### CA-3 (Alternatif): L'affectation alimente le plan de charge
```gherkin
GIVEN Marc affecte Julie Martin au projet PRJ-0042 avec :
     rôle = "Développeuse Back-end", période = 2026-10-01 au 2027-01-31, charge prévisionnelle = 60 j
WHEN Sophie consulte le plan de charge de Julie Martin pour le 4e trimestre 2026
THEN les 60 j de PRJ-0042 apparaissent dans le plan de charge de Julie
  AND le taux d'occupation prévisionnel de Julie intègre cette charge
  AND si le total des affectations de Julie dépasse sa capacité disponible, une alerte de surcharge est affichée à Sophie
```

### CA-4 (Alternatif): Affectation avec rôle et période correctement saisie
```gherkin
GIVEN le projet PRJ-0042 existe au statut "En cours"
WHEN Marc ajoute l'affectation : collaborateur = "Julie Martin", rôle = "Développeuse Back-end",
     date début = 2026-10-01, date fin = 2027-01-31, charge prévisionnelle = 60 j
THEN l'affectation est enregistrée et visible dans l'onglet "Équipe projet"
  AND Julie Martin reçoit une notification "Vous avez été affectée au projet PRJ-0042"
  AND l'accès à l'imputation est ouvert à Julie pour la période 2026-10-01 / 2027-01-31
```

### CA-5 (Erreur): Tentative d'imputation hors période d'affectation refusée
```gherkin
GIVEN Julie Martin est affectée au projet PRJ-0042 du 2026-10-01 au 2027-01-31
WHEN Julie tente de saisir une imputation sur PRJ-0042 pour la semaine du 2027-02-03 (hors période)
THEN la saisie est refusée avec le message "Imputation hors période d'affectation : votre affectation sur ce projet s'est terminée le 31/01/2027"
  AND une ouverture exceptionnelle peut être demandée par Marc pour régulariser si nécessaire
```

### CA-6 (Erreur): Affectation d'un collaborateur inactif ou hors période du projet refusée, et imputation sans affectation ni ouverture tracée refusée (EF-PRJ-20, RG-TMP-1)
```gherkin
GIVEN le collaborateur Robert Durand a le statut "Inactif" dans le système
  AND le projet PRJ-0042 se déroule du 2026-10-01 au 2027-01-31
WHEN Marc tente d'affecter Robert Durand au projet PRJ-0042 pour la période 2026-10-01 au 2026-12-31
THEN l'affectation est refusée avec le message "Collaborateur inactif : Robert Durand ne peut pas être affecté à un projet (RG-TMP-1)"
  AND aucune affectation n'est créée dans le système

GIVEN Julie Martin est affectée au projet PRJ-0042 du 2026-10-01 au 2027-01-31
  AND le projet PRJ-0042 a une date de fin contractuelle au 2027-01-31
WHEN Marc tente de créer une affectation sur PRJ-0042 avec une date de fin au 2027-06-30 (postérieure à la fin du projet)
THEN l'affectation est refusée avec le message "La période d'affectation (jusqu'au 30/06/2027) dépasse la date de fin du projet (31/01/2027) — créez d'abord un avenant de prolongation (EF-PRJ-20)"
  AND aucune affectation hors périmètre du projet n'est enregistrée
```

## Tasks

| ID | Type | Description | Statut | Estimation |
|----|------|-------------|--------|------------|
| - | - | - | 🔴 | - |

## Progression

0/0 tasks complétées (0%)

## Definition of Done

- [ ] Tous les critères d'acceptation validés
- [ ] Code reviewé
- [ ] Tests unitaires passent
- [ ] Tests d'intégration passent
- [ ] Documentation mise à jour

---

## Notes

EF-PRJ-19 : une affectation comporte le collaborateur, le rôle sur le projet, la période (date début/fin), la charge prévisionnelle en jours. Elle ouvre automatiquement les droits d'imputation pour la période concernée et alimente le plan de charge (US plan de charge). EF-PRJ-20 : par défaut, seuls les collaborateurs affectés peuvent imputer sur le projet. Une ouverture exceptionnelle est possible avec traçabilité obligatoire (auteur, date, période, motif). Les ouvertures exceptionnelles sont révoquées automatiquement à l'issue de la période accordée.
