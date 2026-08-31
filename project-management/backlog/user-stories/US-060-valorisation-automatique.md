# US-060: Valorisation automatique après validation (≤ 15 minutes)

## Métadonnées
- **ID**: US-060
- **EPIC**: EPIC-003
- **Sprint**: Sprint 3
- **Statut**: 🔴 To Do
- **Points**: 8
- **Persona**: P2 (Marc — chef de projet), P6 (Directeur financier / contrôleur de gestion)
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: EF-TMP-29 (alimentation automatique des indicateurs coûts projet, marge et taux d'occupation après validation des temps, délai maximum 15 minutes), INV-2 (valorisation figée à la date de validation : un changement de taux ultérieur ne réécrit pas les données historiques), INV-3 (intégrité du calcul : le taux appliqué est le taux en vigueur à la date de validation, conservé dans l'historique), ENF-PERF-5 (performance : valorisation de 1 000 imputations en ≤ 15 minutes, traitement asynchrone)
- **Dépend de**: US-001 (socle multi-tenant), US-003 (RBAC/HAB), US-011 (taux journaliers et mensuels collaborateur), US-055 (validation des temps — déclencheur), US-005 (modèle analytique projet — structure des coûts)
- **Spec Technique**: EF-TMP-29, INV-2, INV-3, ENF-PERF-5

## User Story

**En tant que** chef de projet (Marc) et directeur financier (P6),
**je veux** que les indicateurs financiers de mes projets (coûts, marge, taux d'occupation) soient automatiquement mis à jour dans les 15 minutes suivant la validation des temps, avec une valorisation figée à la date de validation,
**afin de** disposer d'une vision financière fiable et temps-réel sans ressaisie ni calcul manuel, et garantir l'intégrité des données historiques indépendamment des évolutions tarifaires futures.

## Critères d'Acceptation

### CA-1 (Nominal) : Indicateurs à jour en moins de 15 minutes après validation des temps

```gherkin
GIVEN Marc valide les imputations de la semaine 35 pour 10 collaborateurs (150 imputations, tous projets confondus) à 14h00
  AND chaque collaborateur a un taux journalier défini dans US-011
  AND les projets ont un budget et un modèle analytique configurés (US-005)
WHEN la validation est confirmée à 14h00
THEN à 14h15 au plus tard :
  - Le coût réel de la semaine 35 est calculé et affiché dans le dashboard financier du projet
  - La marge brute projet est mise à jour (budget - coût réel cumulé)
  - Le taux d'occupation des collaborateurs est recalculé (heures imputées / capacité)
  AND un indicateur "Dernière mise à jour de la valorisation : 14:12" est visible dans le dashboard
  AND le traitement asynchrone n'a bloqué aucun autre usage de l'application pendant le calcul
```

### CA-2 (Alternatif) : Valorisation figée à la date de validation — changement de taux ultérieur sans impact rétroactif

```gherkin
GIVEN le taux journalier de Camille était de 600 €/j au moment de la validation de la semaine 35 (28/08/2026)
  AND la valorisation de la semaine 35 est calculée et figée : 5j × 600 €/j = 3 000 €
WHEN le taux journalier de Camille est révisé à 650 €/j à compter du 01/09/2026
  AND un utilisateur consulte le coût de la semaine 35 le 15/09/2026
THEN le coût de la semaine 35 affiché est toujours 3 000 € (taux de 600 €/j appliqué à la validation)
  AND le nouveau taux de 650 €/j ne s'applique qu'aux imputations validées à partir du 01/09/2026
  AND le taux utilisé pour chaque imputation est visible dans l'audit trail : "Taux appliqué : 600 €/j — Taux en vigueur au 28/08/2026"
  AND aucun recalcul rétroactif n'est déclenché par le changement de taux
```

### CA-3 (Alternatif) : Performance — valorisation de 1 000 imputations en ≤ 15 minutes

```gherkin
GIVEN un tenant de grande taille soumet à la validation 1 000 imputations simultanément (fin de mois)
  AND le traitement de valorisation démarre immédiatement après la validation
WHEN le processus de valorisation asynchrone s'exécute
THEN 100 % des 1 000 imputations sont valorisées en ≤ 15 minutes
  AND les indicateurs financiers (coûts, marges, taux) sont mis à jour pour tous les projets concernés
  AND un rapport de traitement est disponible dans l'administration : "Valorisation du 31/08/2026 — 1 000 imputations traitées en 12 min 34 s"
  AND aucune imputation n'est partiellement valorisée (traitement atomique par lot)
```

### CA-4 (Erreur) : Taux manquant pour un collaborateur — traitement partiel avec alerte

```gherkin
GIVEN la validation de la semaine 35 inclut 10 collaborateurs
  AND le taux journalier de l'un des collaborateurs (Jean Dupont) n'est pas renseigné dans US-011
WHEN le processus de valorisation démarre
THEN les imputations des 9 autres collaborateurs (avec taux définis) sont valorisées normalement
  AND l'imputation de Jean Dupont est marquée "Non valorisée — taux manquant"
  AND une alerte est envoyée au gestionnaire RH / admin : "Valorisation incomplète — taux journalier manquant pour Jean Dupont (semaine 35)"
  AND un lien direct vers le profil de Jean Dupont dans US-011 est inclus dans l'alerte pour correction rapide
  AND dès que le taux est renseigné, la valorisation de l'imputation se déclenche automatiquement (déclencheur asynchrone)
```

### CA-5 (Erreur) : Valorisation sur période clôturée — recalcul bloqué sans réouverture formelle

```gherkin
GIVEN la période "Août 2026" est clôturée (US-057)
  AND un administrateur tente de déclencher manuellement un recalcul de valorisation sur août 2026
WHEN la requête POST /api/valorisation/recompute?period=2026-08 est reçue
THEN l'API retourne HTTP 423 Locked avec le message "La période Août 2026 est clôturée — recalcul impossible sans réouverture formelle (US-057)"
  AND aucun recalcul n'est effectué
  AND si une réouverture formelle est accordée (US-057) et que le taux change sur la période ouverte, le recalcul peut être déclenché manuellement par un rôle habilité
  AND le recalcul sur période rouverte est tracé dans le journal d'audit avec auteur, date et périmètre recalculé
```

## Critères UI/UX

### Web
- Le dashboard financier projet affiche un indicateur de fraîcheur "Mise à jour il y a X min" pour que l'utilisateur sache si la valorisation est en cours ou terminée.
- Un indicateur de progression est affiché lors d'un traitement de valorisation en cours : "Valorisation en cours (642/1000 imputations traitées)".
- L'audit trail du taux appliqué est accessible depuis chaque ligne d'imputation valorisée (icône ℹ️) sans navigation vers une autre page.
- En cas d'alerte de valorisation incomplète (CA-4), un bandeau d'avertissement est affiché dans le dashboard financier jusqu'à résolution.

### Mobile
- Sur mobile, les indicateurs financiers (coûts, marge, taux d'occupation) sont disponibles en lecture via le dashboard projet (version simplifiée : 3 KPIs en haut de la fiche projet).
- Les alertes de valorisation incomplète sont relayées par notification push et in-app.
- Le détail de l'audit trail n'est pas disponible sur mobile (orienté desktop pour les contrôleurs de gestion).

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

**INV-2 et INV-3 — Valorisation figée** : le taux appliqué lors de la valorisation est stocké dans l'enregistrement de valorisation lui-même (`snapshot_taux_journalier`, `snapshot_taux_date`), pas uniquement dans la table des taux. Ce snapshot garantit l'intégrité historique même si la table des taux est modifiée ultérieurement. C'est un invariant architectural non négociable.

**ENF-PERF-5 — Traitement asynchrone** : la valorisation est déclenchée par un événement (message dans une queue après validation) et traite les imputations par lots de 100 (configurable). Le seuil de 15 minutes pour 1 000 imputations implique un throughput de ≥ 67 imputations/seconde, ce qui doit être validé en test de charge sur l'environnement de staging.

**Indicateurs alimentés** : la valorisation alimente (a minima) les indicateurs suivants, définis dans US-005 (modèle analytique) :
- Coût réel par projet et par période
- Marge brute (budget vendu - coût réel)
- Taux d'occupation par collaborateur (heures imputées / capacité)
- Coût moyen par type d'activité

**Dépendances critiques** : US-011 (taux) doit être livré avant US-060 et doit garantir que chaque collaborateur actif a un taux journalier en vigueur pour chaque journée où il peut être affecté. US-055 (validation) déclenche la valorisation via un événement domaine ("TempsValidés") — le couplage est par événement, pas par appel direct.
