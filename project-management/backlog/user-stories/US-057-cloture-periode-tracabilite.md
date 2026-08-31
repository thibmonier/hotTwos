# US-057: Clôture de période et traçabilité des modifications

## Métadonnées
- **ID**: US-057
- **EPIC**: EPIC-003
- **Sprint**: Sprint 3
- **Statut**: 🔴 To Do
- **Points**: 5
- **Persona**: P2 (Marc — chef de projet), ADMIN (administrateur tenant)
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: EF-TMP-22 (verrouillage des saisies après clôture de période et déclenchement des calculs aval), EF-TMP-23 (traçabilité complète de toute modification d'un temps validé : auteur, date, valeur avant, valeur après, motif), RG-TMP-6 (période clôturée : toute réouverture est formelle, tracée et réalisée par un rôle habilité), INV-7 (intégrité des données historiques : un temps validé et clôturé ne peut être modifié qu'après réouverture formelle tracée)
- **Dépend de**: US-001 (socle multi-tenant), US-003 (RBAC/HAB), US-055 (validation des temps — données à clôturer)
- **Spec Technique**: EF-TMP-22, EF-TMP-23, RG-TMP-6, INV-7

## User Story

**En tant que** administrateur tenant et chef de projet habilité (Marc),
**je veux** clôturer une période de saisie pour verrouiller toutes les imputations, déclencher les calculs aval et tracer toute modification ultérieure avec auteur, date, valeurs avant/après et motif,
**afin de** garantir l'intégrité des données historiques et la conformité des rapports financiers et RH.

## Critères d'Acceptation

### CA-1 (Nominal) : Clôture de période — verrouillage des saisies et déclenchement des calculs aval

```gherkin
GIVEN l'administrateur souhaite clôturer la période "Août 2026" pour le tenant
  AND toutes les imputations de la période sont au statut "Validé"
WHEN il accède à "Administration > Périodes" et sélectionne la période "Août 2026"
  AND clique "Clôturer la période" et confirme en saisissant "CLÔTURE AOÛT 2026" dans le champ de confirmation
THEN la période "Août 2026" passe au statut "Clôturée"
  AND toutes les imputations de la période sont verrouillées (statut LOCKED, non modifiables)
  AND les calculs de valorisation (US-060), de facturation et de charges sont déclenchés automatiquement dans la foulée
  AND un événement "periode_cloturee" est enregistré dans le journal d'audit avec : auteur, horodatage, période, nombre d'imputations verrouillées
  AND les collaborateurs reçoivent une notification "La période Août 2026 est clôturée — vos imputations sont définitives"
```

### CA-2 (Alternatif) : Modification d'un temps validé et clôturé — réouverture formelle obligatoire avec trace

```gherkin
GIVEN la période "Août 2026" est clôturée
  AND l'imputation de Camille du 12/08/2026 (4h P-Alpha, statut LOCKED) doit être corrigée (erreur de projet)
WHEN Marc tente de modifier directement l'imputation depuis la vue de saisie
THEN la vue indique "Cette imputation appartient à une période clôturée — modification impossible sans réouverture"
  AND un bouton "Demander la réouverture" est proposé à Marc (habilité en tant que chef de projet)
  AND Marc soumet une demande de réouverture avec le motif "Erreur de projet : P-Alpha → P-Beta pour le 12/08"
  AND l'administrateur approuve la réouverture
  AND la réouverture est tracée : demandeur Marc, approbateur Admin, date, motif, période concernée, durée de validité (48h)
  AND après réouverture, Marc modifie l'imputation ; la modification est tracée : auteur, date, valeur avant (4h P-Alpha), valeur après (4h P-Beta), motif
  AND la période se reclôture automatiquement après la durée de validité
```

### CA-3 (Alternatif) : Tentative de clôture avec imputations non encore validées — avertissement et blocage optionnel

```gherkin
GIVEN la période "Septembre 2026" comporte 5 imputations au statut "Soumis" non encore validées
  AND 3 collaborateurs n'ont pas encore soumis leurs imputations
WHEN l'administrateur tente de clôturer la période "Septembre 2026"
THEN un avertissement s'affiche : "8 imputations non finalisées (5 en attente de validation, 3 non soumises). Êtes-vous sûr de vouloir clôturer ?"
  AND l'administrateur peut choisir :
    - "Clôturer malgré tout" (avec trace de la décision)
    - "Annuler et traiter les imputations en retard"
  AND si "Clôturer malgré tout" est choisi, les imputations non validées sont marquées "Exclues de la clôture" avec une note explicative
```

### CA-4 (Erreur) : Modification d'un temps verrouillé sans réouverture — refus technique

```gherkin
GIVEN la période "Août 2026" est clôturée et les imputations sont au statut LOCKED
WHEN une requête API PUT /api/timesheets/{id} avec un JWT valide tente de modifier une imputation LOCKED
  AND la requête ne passe pas par le circuit de réouverture formelle
THEN l'API retourne HTTP 423 Locked avec le message "Cette imputation appartient à une période clôturée (Août 2026). Demandez une réouverture formelle."
  AND aucune modification n'est appliquée en base de données
  AND la tentative est loggée dans le journal d'audit : "tentative_modification_periode_cloturee" avec identité de l'appelant et identifiant de l'imputation
```

### CA-5 (Erreur) : Réouverture par un rôle non habilité — refus et trace

```gherkin
GIVEN Camille possède uniquement le rôle "Collaborateur"
  AND la période "Août 2026" est clôturée
WHEN Camille tente d'accéder à la fonctionnalité de réouverture via l'API POST /api/periods/reopening-requests
THEN l'API retourne HTTP 403 Forbidden avec le message "Rôle 'gestionnaire_periodes' requis pour demander une réouverture"
  AND aucune demande de réouverture n'est créée
  AND la tentative est loggée : "tentative_reouverture_non_habilitee" avec l'identité de Camille et la période visée
```

## Critères UI/UX

### Web
- La liste des périodes est présentée avec un code couleur : 🟢 Ouverte, 🟡 En clôture (calculs en cours), 🔴 Clôturée.
- Le bouton "Clôturer" est conditionnel (visible uniquement aux rôles habilités) et déclenche une modale de confirmation avec saisie d'un code de validation (éviter les clics accidentels).
- La trace des modifications est accessible via un bouton "Historique" sur chaque imputation clôturée, affichant un tableau chronologique des modifications avec colonnes : Date, Auteur, Avant, Après, Motif.
- Les imputations verrouillées sont visuellement distinguées par un icône cadenas (🔒) dans la vue de saisie.

### Mobile
- Sur mobile, la clôture de période n'est pas disponible (action administrative non mobile) ; une redirection vers la version web est proposée.
- La consultation de l'historique des modifications est disponible en lecture seule sur mobile.

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

**RG-TMP-6 et INV-7** : le verrouillage technique des imputations clôturées est implémenté à deux niveaux :
1. Niveau applicatif : le statut LOCKED est vérifié avant toute opération de modification (service layer).
2. Niveau base de données : un trigger PostgreSQL peut être activé pour refuser les UPDATE/DELETE sur les imputations avec période clôturée (défense en profondeur).

**Traçabilité des modifications (EF-TMP-23)** : le journal de traçabilité est immuable (INSERT uniquement, pas d'UPDATE ni de DELETE sur les entrées de log). Il est stocké dans une table dédiée `timesheet_audit_log` et doit être accessible aux auditeurs avec une rétention minimale de 7 ans (conformité légale).

**Durée de validité de la réouverture** : par défaut 48 heures ouvrées. Paramétrable par le tenant administrateur. Après expiration, les imputations se reclôturent automatiquement, qu'elles aient été modifiées ou non.

**Déclenchement des calculs aval** : la clôture déclenche de façon asynchrone (message queue) les traitements de valorisation (US-060), de facturation et de paie. Un statut de traitement est visible dans l'interface d'administration jusqu'à la fin de tous les calculs.
