# US-038: Clôture opérationnelle du projet

## Métadonnées
- **ID**: US-038
- **EPIC**: EPIC-002
- **Sprint**: 3
- **Statut**: 🔴 To Do
- **Points**: 3
- **Persona**: P2 (Marc – Chef de projet)
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: EF-PRJ-22, RG-PRJ-5
- **Dépend de**: US-001 (socle multitenant), US-003 (socle authentification), US-030 (création projet et statuts), US-033 (budget charge/montant), US-034 (engagements externes), US-035 (avancement et RAF)
- **Spec Technique**: PRJ-22 – Clôture opérationnelle

## User Story

**En tant que** Marc, chef de projet,
**je veux** clôturer un projet pour fermer définitivement les imputations tout en conservant l'accès aux données historiques et aux agrégats financiers,
**afin de** garantir l'intégrité des données après la fin du projet et permettre le reporting consolidé sur les projets terminés.

## Critères d'Acceptation

### CA-1 (Nominal): Aucune imputation après clôture sans réouverture tracée
```gherkin
GIVEN le projet PRJ-0042 "Refonte SI" vient d'être clôturé par Marc le 2027-03-31
  AND la clôture a fermé toutes les imputations
WHEN la développeuse Julie tente de saisir une imputation sur PRJ-0042 pour la semaine du 2027-04-07
THEN la saisie est refusée avec le message "Projet clôturé le 31/03/2027 – imputations fermées (RG-PRJ-5)"
  AND aucune imputation n'est créée
```

### CA-2 (Alternatif): Projet clôturé reste consultable et inclus dans les agrégats
```gherkin
GIVEN le projet PRJ-0042 est clôturé depuis le 2027-03-31
WHEN Élodie consulte le tableau de bord des marges de l'année 2027
THEN PRJ-0042 apparaît dans le tableau avec ses données financières finales
  AND la marge finale est incluse dans les agrégats de l'agence pour l'exercice 2027
  AND un badge "Clôturé" est affiché sur la fiche du projet
  AND toutes les données (imputations, jalons, budget, avenants) restent consultables en lecture seule
```

### CA-3 (Alternatif): Réouverture exceptionnelle tracée pour imputation après clôture
```gherkin
GIVEN le projet PRJ-0042 est clôturé depuis le 2027-03-31
  AND une imputation oubliée de 2 j doit être régularisée
WHEN Marc demande une réouverture exceptionnelle du projet avec motif "Régularisation imputation manquante semaine 13"
  AND la réouverture est validée par ADMIN
THEN la fiche du projet indique "Réouvert temporairement" avec la date et le motif
  AND les imputations deviennent possibles pendant la fenêtre de réouverture
  AND la réouverture est tracée : demandeur = Marc, valideur = ADMIN, date, motif
  AND la clôture est automatiquement rétablie à l'issue de la fenêtre
```

### CA-4 (Alternatif): Prérequis de clôture vérifiés avant validation
```gherkin
GIVEN le projet PRJ-0042 a des jalons en statut "En attente" non atteints
  AND il a des engagements externes au statut "Engagé" non soldés
WHEN Marc déclenche la clôture du projet
THEN le système affiche une liste des points bloquants :
     "Jalons non atteints : J3 Recette"
     "Engagements non soldés : Sous-traitance intégration 6 000 €"
  AND Marc doit confirmer explicitement avoir traité ou accepté de clôturer malgré ces éléments
  AND la clôture est enregistrée avec la liste des éléments en cours au moment de la clôture
```

### CA-5 (Alternatif): Suivi financier maintenu après clôture (pas de gel)
```gherkin
GIVEN le projet PRJ-0042 est clôturé depuis le 2027-03-31
  AND une facture relative à ce projet est reçue le 2027-04-15
WHEN le responsable facturation enregistre la réception de cette facture fournisseur
THEN la facture est rattachée au projet PRJ-0042 clôturé
  AND le bilan financier final du projet est mis à jour avec cet engagement soldé
  AND la marge finale recalculée apparaît dans les agrégats de reporting
  AND aucune imputation de temps n'est ouverte pour autant
```

### CA-6 (Erreur): Clôture bloquée si des imputations non validées existent (RG-PRJ-5)
```gherkin
GIVEN le projet PRJ-0042 comporte 3 imputations de la semaine 12 au statut "Soumise – en attente de validation"
  AND aucune de ces imputations n'a encore été validée ou rejetée par le manager
WHEN Marc déclenche la clôture opérationnelle du projet PRJ-0042
THEN le système affiche un avertissement bloquant : "Clôture impossible : 3 imputation(s) non validée(s) détectées — validez ou rejetez toutes les imputations en attente avant de clôturer (RG-PRJ-5)"
  AND la clôture n'est pas enregistrée
  AND Marc reçoit la liste des imputations en attente avec l'auteur, la semaine et le nombre de jours concernés
```

### CA-7 (Erreur): Imputation refusée après clôture sans réouverture formelle tracée par un rôle habilité (RG-PRJ-5, RG-TMP-6)
```gherkin
GIVEN le projet PRJ-0042 est clôturé depuis le 2027-03-31
  AND aucune réouverture exceptionnelle n'a été accordée ni tracée
WHEN un collaborateur tente de saisir une imputation sur PRJ-0042 pour la semaine du 2027-04-07
     (que ce soit via l'interface ou directement via l'API de saisie de temps)
THEN la tentative est rejetée avec le message "Imputations fermées – projet PRJ-0042 clôturé le 31/03/2027 ; une réouverture formelle validée par ADMIN est requise (RG-TMP-6)"
  AND aucune imputation n'est créée dans le système
  AND l'événement est enregistré dans l'audit log : date, auteur, type d'accès, ressource cible
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

EF-PRJ-22 : la clôture opérationnelle ferme les imputations de temps mais ne gèle pas le suivi financier (facturation et engagements peuvent encore être mis à jour). RG-PRJ-5 : un projet clôturé reste consultable en lecture seule et ses données sont incluses dans les agrégats de reporting. La réouverture est une opération exceptionnelle qui nécessite une validation ADMIN et laisse une trace durable dans l'audit log. La clôture n'est pas irréversible mais sa levée doit être explicitement tracée.
