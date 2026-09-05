# US-030: Création de projet et cycle de vie

## Métadonnées
- **ID**: US-030
- **EPIC**: EPIC-002
- **Sprint**: 2
- **Statut**: ✅ Done (livré Sprint 6)
- **Points**: 5
- **Persona**: P2 (Marc – Chef de projet)
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: EF-PRJ-1, EF-PRJ-4, RG-PRJ-1
- **Dépend de**: US-001 (socle multitenant), US-003 (socle authentification), US-010 (organisation/clients), US-011 (profils et taux)
- **Spec Technique**: PRJ-1 – Création et cycle de vie du projet

## User Story

**En tant que** Marc, chef de projet,
**je veux** créer un projet en renseignant obligatoirement un client, un responsable et un budget, puis faire évoluer son statut tout au long de sa vie,
**afin de** disposer d'un référentiel projet fiable qui conditionne les actions permises (imputations, facturation) selon l'étape en cours.

## Critères d'Acceptation

### CA-1 (Nominal): Création d'un projet complet avec tous les champs obligatoires
```gherkin
GIVEN je suis connecté en tant que chef de projet
  AND je suis sur la page "Nouveau projet"
WHEN je renseigne : client = "Acme Corp", responsable = "Marc Dupont", budget = 120 000 €,
     contractualisation = "Forfait", date début = 2026-09-01, date fin = 2027-03-31
  AND je clique sur "Créer le projet"
THEN le projet est créé avec le statut initial "En préparation"
  AND un identifiant unique PRJ-XXXX est attribué automatiquement
  AND le projet apparaît dans la liste des projets actifs
```

### CA-2 (Alternatif): Transition de statut conditionne les imputations
```gherkin
GIVEN le projet PRJ-0042 est au statut "En préparation"
  AND le collaborateur Sophie est affectée à ce projet
WHEN Sophie tente de saisir une imputation sur PRJ-0042
THEN la saisie est refusée avec le message "Imputations non autorisées : projet en préparation"
WHEN Marc fait passer le statut à "En cours"
  AND Sophie tente de saisir une imputation
THEN la saisie est acceptée normalement
```

### CA-3 (Alternatif): Transition de statut conditionne la facturation
```gherkin
GIVEN le projet PRJ-0042 est au statut "Livré – en attente de réception"
WHEN le responsable facturation tente d'émettre une facture sur ce projet
THEN la facture peut être générée car le statut l'autorise
WHEN le statut est "En préparation"
  AND le responsable tente la même action
THEN l'action est bloquée avec le message "Facturation non disponible pour ce statut"
```

### CA-4 (Erreur): Création refusée sans client
```gherkin
GIVEN je suis sur le formulaire "Nouveau projet"
WHEN je renseigne responsable et budget mais je laisse le champ "Client" vide
  AND je clique sur "Créer le projet"
THEN le système affiche l'erreur "Le client est obligatoire (RG-PRJ-1)"
  AND le projet n'est pas créé
```

### CA-5 (Erreur): Création refusée sans budget
```gherkin
GIVEN je suis sur le formulaire "Nouveau projet"
WHEN je renseigne client et responsable mais je laisse le champ "Budget" vide
  AND je clique sur "Créer le projet"
THEN le système affiche l'erreur "Le budget est obligatoire (RG-PRJ-1)"
  AND le projet n'est pas créé
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

Les 7 statuts par défaut sont : En préparation, En cours, En attente client, Livré – en attente de réception, Réceptionné, Clôturé, Annulé. Les transitions autorisées et les actions permises pour chaque statut sont configurables par ADMIN (EF-PRJ-4). RG-PRJ-1 : un projet ne peut être créé sans client, responsable ET budget.
