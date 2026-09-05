# US-059: Synthèse d'activité et planning depuis l'écran de saisie

## Métadonnées
- **ID**: US-059
- **EPIC**: EPIC-003
- **Sprint**: Sprint 1
- **Statut**: ✅ Done (livré Sprint 5)
- **Points**: 3
- **Persona**: P1 (Camille — collaborateur)
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: EF-TMP-26 (synthèse d'activité par projet et par type accessible en 1 clic depuis la vue de saisie), EF-TMP-27 (planning à venir du collaborateur affiché depuis la vue de saisie sans navigation supplémentaire), RSQ-1 (atténuation du risque de résistance à la saisie par une contrepartie visible : le collaborateur voit l'utilité de ses données)
- **Dépend de**: US-001 (socle multi-tenant), US-003 (RBAC/HAB), US-050 (saisie de base — données d'activité)
- **Spec Technique**: EF-TMP-26, EF-TMP-27, RSQ-1

## User Story

**En tant que** collaborateur (Camille),
**je veux** accéder en un clic depuis mon écran de saisie à une synthèse de mon activité par projet et à mon planning à venir,
**afin de** voir immédiatement l'utilité de mes saisies (charge prévisionnelle, répartition de mon temps) et d'adhérer naturellement à la démarche de saisie.

## Critères d'Acceptation

### CA-1 (Nominal) : Synthèse d'activité accessible en 1 clic depuis la vue de saisie

```gherkin
GIVEN Camille est sur la vue de saisie hebdomadaire et a déjà saisi des imputations sur les 4 dernières semaines
WHEN elle clique sur le bouton "Ma synthèse" (accessible depuis la vue de saisie sans navigation de page)
THEN un panneau latéral ou une vue inline s'ouvre en moins de 1 seconde affichant :
  - Répartition de son temps par projet sur les 4 dernières semaines (graphique en barres ou camembert)
  - Total d'heures par type d'activité (production, formation, avant-vente, etc.) sur la même période
  - Taux d'occupation moyen (heures imputées / heures attendues) en %
  AND les données affichées sont celles de Camille uniquement (périmètre RBAC automatique)
  AND Camille peut refermer le panneau et revenir à la saisie sans perte de données en cours
```

### CA-2 (Alternatif) : Planning à venir affiché depuis la vue de saisie

```gherkin
GIVEN Camille est sur la vue de saisie de la semaine 35
  AND elle est affectée en planning sur les semaines 36, 37 et 38 (données US-037)
WHEN elle clique sur l'onglet "Mon planning à venir" dans le panneau latéral
THEN son planning des 4 prochaines semaines s'affiche :
  - Projets sur lesquels elle est affectée, avec les dates et taux d'affectation
  - Absences déjà validées sur la période
  - Charge théorique hebdomadaire totale vs capacité (40h/semaine standard)
  AND le planning s'affiche en moins de 1 seconde (données préchargées)
  AND Camille peut naviguer entre "Synthèse passée" et "Planning à venir" sans rechargement
```

### CA-3 (Alternatif) : Synthèse accessible même sans historique — état vide explicite

```gherkin
GIVEN Camille vient de rejoindre l'agence et n'a pas encore d'imputations validées
WHEN elle ouvre le panneau "Ma synthèse"
THEN la vue affiche "Aucune donnée d'activité disponible pour l'instant. Votre synthèse apparaîtra après votre première semaine de saisie."
  AND les graphiques sont remplacés par des emplacements vides (skeleton) avec message explicatif
  AND le planning à venir est affiché normalement si des affectations existent déjà (US-037)
  AND aucune erreur technique n'est affichée
```

### CA-4 (Erreur) : Données d'un autre collaborateur non accessibles depuis la synthèse

```gherkin
GIVEN Camille est connectée et consulte sa synthèse
WHEN l'API /api/activity-summary est appelée avec le JWT de Camille
THEN la réponse contient uniquement les données de Camille (filtrage tenant + collaborateur automatique)
  AND un paramètre ?user_id= d'un autre collaborateur dans la requête retourne HTTP 403 Forbidden
  AND aucune donnée d'un autre collaborateur n'est accessible, même pour un collaborateur du même projet
```

### CA-5 (Erreur) : Panneau synthèse ne perturbe pas la saisie en cours

```gherkin
GIVEN Camille a saisi 3h sur P-Alpha dans la vue hebdomadaire mais n'a pas encore soumis
WHEN elle ouvre le panneau "Ma synthèse" puis le referme
THEN les valeurs saisies (3h sur P-Alpha) sont intactes dans la grille de saisie
  AND aucune soumission partielle ou sauvegarde automatique n'a été déclenchée
  AND le focus est rétabli sur la cellule de saisie précédente au retour du panneau
```

## Critères UI/UX

### Web
- Le bouton "Ma synthèse" est intégré dans la barre d'actions de la vue de saisie (pas un lien de navigation qui change de page).
- Le panneau latéral s'ouvre en superposition (drawer/panel) à droite de la vue de saisie, de largeur 30-40 % de l'écran, sans faire disparaître la grille de saisie.
- Les graphiques sont simples et lisibles : camembert pour la répartition par projet (max 7 tranches avec regroupement "Autres"), barres horizontales pour les types d'activité.
- Le planning est présenté sous forme de mini-calendrier semaine ou de liste structurée par semaine.
- Les deux onglets "Passé" et "À venir" sont accessibles depuis le panneau sans le fermer.

### Mobile
- Sur mobile, le panneau devient un bottom-sheet (50-80 % de la hauteur d'écran) avec scroll interne.
- Le bouton "Ma synthèse" est accessible depuis la vue quotidienne (US-052) via un onglet ou icône en bas de l'écran.
- Les graphiques s'adaptent à la largeur mobile (graphiques en donut plutôt qu'en camembert, barres verticales plutôt qu'horizontales).
- La fermeture du panneau par swipe vers le bas réactive la saisie immédiatement.

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

**RSQ-1 (Résistance à la saisie)** : la contrepartie visible est un levier clé d'adoption. Des études internes ont montré que les collaborateurs perçoivent la saisie comme "utile pour eux" lorsqu'ils peuvent voir en retour leur charge de travail, leur répartition de temps et leur planning. Cette story contribue directement à la réduction du risque RSQ-1 (taux d'abandon de la saisie) et à l'objectif OBJ-1 (complétude ≥ 90 %).

**EF-TMP-26 — 1 clic** : l'accès à la synthèse ne doit pas nécessiter de quitter la vue de saisie ni d'ouvrir un nouvel onglet de navigateur. Le panneau latéral (drawer) est la solution recommandée pour respecter ce critère de proximité fonctionnelle.

**Données de synthèse** : la période couverte par la synthèse (4 semaines par défaut) est paramétrable par le collaborateur (sélecteur de période). Les données sont calculées uniquement sur les imputations au statut VALIDÉ ou SOUMIS (les propositions IA non confirmées, statut PROPOSITION, sont exclues — voir RG-TMP-4).

**Affichage du planning** : EF-TMP-27 nécessite l'accès aux données d'affectation (US-037). Si US-037 n'est pas encore livrée, le planning s'affiche avec un message "Planning non disponible — module d'affectation non activé" (dégradation gracieuse).
