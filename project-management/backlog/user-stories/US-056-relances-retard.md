# US-056: Relances automatiques de retard de saisie

## Métadonnées
- **ID**: US-056
- **EPIC**: EPIC-003
- **Sprint**: Sprint 2
- **Statut**: 🔴 To Do
- **Points**: 3
- **Persona**: P1 (Camille — collaborateur, destinataire), P2 (Marc — chef de projet, paramètre les règles)
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: EF-TMP-21 (relances automatiques en cas de retard de saisie : délai avant première relance, canal de notification et escalade paramétrables ; fréquence maximale bornée ; désactivable par le tenant ou par le collaborateur)
- **Dépend de**: US-001 (socle multi-tenant), US-003 (RBAC/HAB), US-050 (saisie de base — source des données de complétude)
- **Spec Technique**: EF-TMP-21

## User Story

**En tant que** chef de projet (Marc) et collaborateur (Camille),
**je veux** que des relances automatiques et paramétrables soient envoyées aux collaborateurs en retard de saisie, avec une fréquence bornée et la possibilité de les désactiver,
**afin d'** atteindre l'objectif de complétude de saisie à J+2 ≥ 90 % (OBJ-1) sans harceler les collaborateurs.

## Critères d'Acceptation

### CA-1 (Nominal) : Relance automatique envoyée au collaborateur en retard selon les paramètres du tenant

```gherkin
GIVEN le tenant a configuré la règle de relance suivante :
  - Délai avant première relance : 2 jours ouvrés après la fin de la semaine à saisir
  - Canal : email + notification in-app
  - Fréquence maximale : 1 relance tous les 2 jours ouvrés
  - Escalade : après 3 relances sans action, notifier également le manager N+1
  AND Camille n'a pas soumis ses imputations de la semaine 35 (fin vendredi 28/08/2026)
WHEN le moteur de relances s'exécute le mardi 01/09/2026 (J+2 ouvré)
THEN Camille reçoit un email et une notification in-app : "Rappel : vos imputations de la semaine 35 (24-28/08) ne sont pas encore soumises. Deadline : vendredi 05/09/2026."
  AND la relance est tracée dans le journal de relances avec : date, canal, destinataire, semaine concernée
  AND si Camille ne soumet toujours pas à J+4 ouvré, une seconde relance est envoyée (dans la borne de fréquence)
  AND à la 3ème relance sans action, Marc reçoit également une notification d'escalade
```

### CA-2 (Alternatif) : Désactivation des relances par le collaborateur (opt-out)

```gherkin
GIVEN Camille reçoit des relances automatiques et choisit de les désactiver temporairement
WHEN elle accède à ses préférences de notification et désactive l'option "Relances de saisie"
THEN elle ne reçoit plus aucune relance automatique tant que l'option est désactivée
  AND un bandeau de rappel discret apparaît dans la vue de saisie si sa semaine est en retard ("Votre saisie de la semaine 35 est incomplète")
  AND le manager (Marc) peut toujours voir que Camille est en retard dans le tableau de complétude (US-058), indépendamment de son opt-out de relance
  AND l'opt-out est loggé avec la date et ne peut pas être forcé en masse par l'administrateur (droit individuel)
```

### CA-3 (Alternatif) : Relance cessant automatiquement après soumission

```gherkin
GIVEN Camille a reçu 1 relance pour la semaine 35 et n'a pas encore soumis
  AND une seconde relance est programmée pour le 03/09/2026
WHEN Camille soumet ses imputations de la semaine 35 le 02/09/2026 à 14h
THEN la relance programmée du 03/09/2026 est annulée automatiquement
  AND aucune relance supplémentaire n'est envoyée pour la semaine 35
  AND le journal de relances enregistre "Relance annulée — saisie soumise le 02/09/2026 à 14:00"
```

### CA-4 (Erreur) : Borne de fréquence respectée — pas plus d'une relance toutes les 48 h ouvrées

```gherkin
GIVEN la fréquence maximale est paramétrée à "1 relance tous les 2 jours ouvrés"
  AND Camille est en retard sur la semaine 35 depuis 6 jours ouvrés
WHEN le moteur de relances calcule le planning d'envoi
THEN il envoie au maximum 1 relance tous les 2 jours ouvrés, soit ≤ 3 relances sur 6 jours
  AND aucune relance n'est envoyée deux fois dans la même journée, quelles que soient les conditions
  AND si le paramètre de fréquence est mal configuré (ex : 0 jours), le système applique un plancher de sécurité de 1 jour ouvré minimum
```

### CA-5 (Erreur) : Désactivation globale des relances par l'administrateur tenant

```gherkin
GIVEN l'administrateur du tenant désactive le module de relances dans les paramètres généraux
WHEN le moteur de relances s'exécute
THEN aucune relance n'est envoyée à aucun collaborateur du tenant
  AND les relances en file d'attente (programmées) sont annulées
  AND la désactivation est tracée dans le journal d'administration avec l'auteur, la date et un motif optionnel
  AND la réactivation ultérieure repart des délais depuis la date de réactivation (pas de rattrapage rétroactif)
```

## Critères UI/UX

### Web
- La vue de configuration des relances (accessible aux admins et chefs de projet habilités) présente les paramètres sous forme de formulaire clair : délai initial, fréquence, canal, escalade.
- Un aperçu simulé ("Prévisualisation des relances pour la semaine en cours") aide Marc à valider la configuration avant de l'activer.
- L'historique des relances envoyées est accessible en lecture seule avec filtres par collaborateur, semaine et canal.

### Mobile
- Les relances reçues apparaissent dans la liste de notifications in-app avec un lien direct vers la semaine à saisir.
- L'opt-out est accessible depuis la notification elle-même ("Me désabonner de ces rappels") sans avoir à naviguer dans les paramètres.

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

**EF-TMP-21 — Paramétrage** : les paramètres suivants sont configurables par tenant via l'interface d'administration : délai avant première relance (en jours ouvrés, valeur par défaut : 2), fréquence maximale (en jours ouvrés entre deux relances, valeur par défaut : 2), canal (email, in-app, les deux), escalade (oui/non, délai avant escalade, destinataire de l'escalade).

**Plancher de fréquence** : une relance ne peut jamais être envoyée plus d'une fois par jour ouvré, quel que soit le paramétrage. Cette contrainte est hardcodée et non paramétrable pour éviter le spam.

**Opt-out individuel** : le droit de désactiver les relances appartient au collaborateur. Un administrateur peut désactiver globalement pour le tenant, mais ne peut pas forcer la réactivation pour un collaborateur qui a opté pour l'opt-out. Cette règle est conforme au principe de minimisation des données (RGPD) et de respect du collaborateur.

**Intégration OBJ-1** : ce mécanisme contribue directement à l'objectif OBJ-1 (taux de complétude ≥ 90 % à J+2). Le tableau de bord de complétude (US-058) permet de mesurer l'efficacité des relances et d'ajuster les paramètres si le taux reste insuffisant.
