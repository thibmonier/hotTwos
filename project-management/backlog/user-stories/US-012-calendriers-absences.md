# US-012: Calendriers de travail et types d'absence

## Métadonnées
- **ID**: US-012
- **EPIC**: EPIC-001
- **Sprint**: Sprint 1
- **Statut**: 🔴 To Do
- **Points**: 5
- **Persona**: ADMIN
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: EF-REF-6, EF-REF-7, EF-REF-8, EF-REF-9, RG-REF-1
- **Dépend de**: US-001 (fondation multi-tenant), US-010 (structure organisationnelle)
- **Spec Technique**: EF-REF-6 (calendrier tenant), EF-REF-7 (calendriers différenciés), EF-REF-8 (types d'absence), EF-REF-9 (fermeture entreprise)

## User Story

**En tant qu'** administrateur tenant,
**je veux** paramétrer des calendriers de travail différenciés par entité, pays ou collaborateur (temps partiel, forfait jours) et définir les types d'absence avec leur impact sur la capacité et leur circuit de validation,
**afin de** calculer avec précision la capacité productive de chaque collaborateur sur n'importe quelle période et alimenter correctement les indicateurs de charge et de disponibilité.

## Critères d'Acceptation

### CA-1 (Nominal) : Collaborateur à 80 % — capacité journalière de 4/5
```gherkin
GIVEN le calendrier tenant "France - 35h" définit 5 jours ouvrés par semaine et 7h/jour
  AND la collaboratrice "Marie Laurent" est configurée avec un contrat à 80 % (temps partiel)
  AND son jour non travaillé est le vendredi
WHEN le système calcule la capacité productive de Marie pour la semaine du 02/03 au 06/03
THEN la capacité est de 4 jours (lundi à jeudi) × 7h = 28h, soit 4/5 de la semaine type
  AND le vendredi est affiché comme "jour non travaillé (temps partiel)" et non comme absence
  AND les indicateurs de taux d'occupation utilisent 4 jours comme base, pas 5
```

### CA-2 (Nominal) : Calendrier différencié par pays (France vs Royaume-Uni)
```gherkin
GIVEN le tenant "ConsultingEurope" a deux entités : "France" et "UK"
  AND le calendrier "France" inclut le 14 juillet comme jour férié
  AND le calendrier "UK" inclut le Bank Holiday d'août mais pas le 14 juillet
  AND deux collaborateurs : "Jean" (calendrier France) et "James" (calendrier UK)
WHEN le système calcule les jours ouvrés du mois de juillet pour les deux collaborateurs
THEN Jean a 22 jours ouvrés (31 jours - week-ends - 14/07)
  AND James a 23 jours ouvrés (31 jours - week-ends, sans le 14/07)
  AND les rapports de capacité distinguent clairement les deux bases de calcul
```

### CA-3 (Alternatif) : Fermeture entreprise — tous les collaborateurs impactés simultanément
```gherkin
GIVEN le tenant "AgenceX" configure une fermeture entreprise du 23/12 au 02/01 inclus (EF-REF-9)
  AND 35 collaborateurs sont actifs à cette date
WHEN la fermeture est enregistrée et activée par l'ADMIN
THEN les 35 collaborateurs voient leur capacité réduite automatiquement sur cette période
  AND la fermeture est visible dans le calendrier individuel de chaque collaborateur
  AND aucune saisie de temps productif ne peut être créée sur ces jours (blocage à la saisie)
  AND un rapport de fermeture liste les jours concernés et le nombre de collaborateurs impactés
```

### CA-4 (Alternatif) : Type d'absence "Congé payé" réduit la capacité et déclenche validation manager
```gherkin
GIVEN le type d'absence "Congé payé" est configuré avec : impact capacité = OUI, circuit = manager N+1
  AND P1 Camille soumet une demande d'absence du 15/07 au 19/07
WHEN la demande est soumise
THEN la capacité de Camille est marquée "en attente de validation" pour la semaine concernée
  AND une notification est envoyée au manager N+1 pour validation
  AND après validation, la capacité de Camille est réduite de 5 jours pour cette semaine
  AND le type d'absence et le statut apparaissent dans le planning de charge
```

### CA-5 (Erreur) : Suppression d'un type d'absence référencé → refus avec désactivation proposée
```gherkin
GIVEN le type d'absence "Formation" est utilisé dans 12 demandes d'absence passées ou en cours
WHEN l'ADMIN tente de supprimer le type "Formation"
THEN la suppression est refusée avec le message "Ce type d'absence est référencé dans 12 entrées. Utilisez la désactivation pour le masquer des futures demandes."
  AND le bouton "Désactiver ce type" est proposé directement dans le message d'erreur
  AND les 12 demandes existantes conservent leur type d'absence (conformément à RG-REF-1)
```

### CA-6 (Erreur) : Durée journalière nulle ou négative dans un calendrier → refus
```gherkin
GIVEN l'ADMIN crée un nouveau calendrier de travail "Forfait UK" pour l'entité "UK"
WHEN l'ADMIN saisit une durée journalière de 0 heure (ou une valeur négative) pour les jours ouvrés
THEN le système refuse l'enregistrement avec le message "La durée journalière doit être strictement supérieure à 0. Valeur saisie : 0 h."
  AND aucun calendrier n'est créé ou modifié
  AND le champ durée journalière est mis en évidence avec l'erreur de validation
  AND le calcul de capacité productive ne peut pas démarrer sur un calendrier avec durée journalière invalide
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

L'architecture de calendrier suit une hiérarchie de résolution : collaborateur > entité > pays > tenant. Si aucun calendrier spécifique n'est configuré pour un collaborateur, le système remonte jusqu'au calendrier tenant par défaut.

Les collaborateurs en "forfait jours" ont une base de capacité en jours, non en heures. Le paramètre `work_mode` (heures | jours) au niveau du contrat collaborateur conditionne le type d'unité affiché dans toutes les interfaces de planning.

EF-REF-9 (fermeture entreprise) est un type de calendrier tenant global qui prend la priorité sur tous les calendriers individuels. La fermeture s'applique même aux collaborateurs ayant un calendrier personnalisé.
