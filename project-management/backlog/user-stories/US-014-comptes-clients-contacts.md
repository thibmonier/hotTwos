# US-014: Référentiel comptes clients et contacts

## Métadonnées
- **ID**: US-014
- **EPIC**: EPIC-001
- **Sprint**: Sprint 2
- **Statut**: 🔴 To Do
- **Points**: 3
- **Persona**: ADMIN / P4 Yann (Commercial)
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: EF-REF-15, EF-REF-16, RG-REF-1
- **Dépend de**: US-001 (fondation multi-tenant)
- **Spec Technique**: EF-REF-15 (comptes clients + hiérarchie groupe/filiale), EF-REF-16 (contacts + rôle + statut)

## User Story

**En tant que** commercial (P4 Yann) et administrateur tenant,
**je veux** gérer un référentiel de comptes clients structuré en hiérarchie groupe/filiale avec les contacts associés (rôle, statut),
**afin de** disposer d'un socle de données client partagé par les modules commercial, projet et facturation, évitant toute saisie dupliquée et assurant la cohérence des informations sur l'ensemble du cycle de vie client.

## Critères d'Acceptation

### CA-1 (Nominal) : Création d'un compte client avec hiérarchie groupe/filiale
```gherkin
GIVEN P4 Yann accède au module de gestion des comptes clients
WHEN Yann crée le compte "Groupe BNP Paribas" (type : Groupe) puis crée "BNP Paribas SA" (type : Filiale) en la rattachant au groupe
  AND il crée une seconde filiale "BNP Paribas Leasing Solutions" rattachée au même groupe
THEN la vue hiérarchique affiche "Groupe BNP Paribas" avec ses deux filiales en sous-niveaux
  AND les projets et devis créés sur une filiale sont consolidables au niveau du groupe
  AND le groupe apparaît dans les filtres de reporting avec agrégation des filiales sous-jacentes
```

### CA-2 (Nominal) : Ajout d'un contact avec rôle et statut sur un compte
```gherkin
GIVEN le compte "BNP Paribas SA" existe dans le référentiel
WHEN Yann ajoute le contact "Claire Dupont" avec : rôle = "Responsable Achats IT", email = claire.dupont@bnp.fr, statut = "Actif"
  AND il ajoute un second contact "Paul Martin" avec rôle = "DSI", statut = "Inactif" (ancien interlocuteur)
THEN la fiche du compte "BNP Paribas SA" liste les deux contacts avec leur rôle et statut respectifs
  AND les vues de création de devis et de projet proposent uniquement les contacts "Actifs" dans les menus déroulants par défaut
  AND les contacts "Inactifs" restent accessibles via un filtre "Afficher les inactifs"
```

### CA-3 (Alternatif) : Recherche d'un compte par nom partiel ou SIREN
```gherkin
GIVEN le référentiel contient 120 comptes clients
  AND le compte "Société Générale Corporate & Investment Banking" existe avec le SIREN "552 120 222"
WHEN Yann saisit "Société" dans la barre de recherche
THEN la liste filtrée affiche tous les comptes contenant "Société" dans leur raison sociale (insensible à la casse)
  AND si Yann saisit "552120222" (sans espaces), le compte est trouvé par SIREN
  AND le résultat affiche le type de compte (Groupe/Filiale) et le nom du groupe parent si applicable
```

### CA-4 (Alternatif) : Fusion de deux comptes doublons
```gherkin
GIVEN les comptes "Capgemini France" (ID : C-047) et "Cap Gemini France" (ID : C-093) existent en doublon avec respectivement 3 et 1 projet(s) associé(s)
WHEN l'ADMIN lance la fusion en désignant "Capgemini France" comme compte maître
THEN les 4 projets sont réassociés au compte maître "Capgemini France"
  AND le compte "Cap Gemini France" est désactivé (non supprimé, conformément à RG-REF-1)
  AND les contacts des deux comptes sont consolidés sur le compte maître (déduplication par email)
  AND un événement d'audit enregistre la fusion avec les IDs des deux comptes et les données migrées
```

### CA-5 (Erreur) : Désactivation d'un compte avec projets actifs → avertissement et confirmation
```gherkin
GIVEN le compte "Airbus Group" est référencé dans 2 projets avec statut "En cours" et 1 devis "En attente"
WHEN l'ADMIN tente de désactiver le compte "Airbus Group"
THEN le système affiche un avertissement : "Ce compte est référencé dans 2 projets actifs et 1 devis en cours. La désactivation le masquera des nouvelles saisies mais n'affectera pas les données existantes."
  AND l'ADMIN doit confirmer explicitement la désactivation
  AND après confirmation, le compte passe en statut "Inactif" et n'apparaît plus dans les menus de création de nouveaux projets/devis
  AND les 2 projets et le devis existants conservent leur référence au compte (conformément à RG-REF-1)
```

### CA-6 (Erreur) : Rattachement groupe/filiale cyclique → refus
```gherkin
GIVEN le compte "Groupe Bouygues" (type : Groupe) possède la filiale "Bouygues Télécom" (type : Filiale)
WHEN l'ADMIN tente de rattacher "Groupe Bouygues" comme filiale de "Bouygues Télécom" (créant le cycle Groupe Bouygues → Bouygues Télécom → Groupe Bouygues)
THEN le système refuse l'opération avec le message "Rattachement impossible : cette relation crée une hiérarchie circulaire entre 'Groupe Bouygues' et 'Bouygues Télécom'."
  AND la hiérarchie existante reste inchangée ("Groupe Bouygues" conserve "Bouygues Télécom" comme filiale)
  AND aucune modification n'est persistée en base de données
  AND la validation anti-cycle est effectuée côté serveur avant toute écriture (indépendamment du type Groupe/Filiale affiché en UI)
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

La hiérarchie groupe/filiale (EF-REF-15) est modélisée par une relation auto-référentielle `parent_account_id` sur la table `client_account`. La profondeur n'est pas limitée en théorie, mais l'UI ne doit exposer que 2 niveaux (Groupe / Filiale) pour garantir la lisibilité.

Le champ SIREN est optionnel mais doit être unique par tenant si renseigné. La validation du format SIREN (9 chiffres) est effectuée côté serveur.

Le référentiel clients est partagé entre les modules CRM (commercial), PRO (projets) et FAC (facturation). Cette US est donc un prérequis fonctionnel pour US-015 (taux de vente par client).
