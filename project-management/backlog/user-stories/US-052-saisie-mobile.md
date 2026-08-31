# US-052: Saisie quotidienne sur mobile

## Métadonnées
- **ID**: US-052
- **EPIC**: EPIC-003
- **Sprint**: Sprint 1
- **Statut**: 🔴 To Do
- **Points**: 3
- **Persona**: P1 (Camille — collaborateur)
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: EF-TMP-6 (saisie quotidienne intégralement réalisable depuis un téléphone sans dégradation fonctionnelle), ENF-UX-3 (interface mobile-first responsive, zones tactiles ≥ 44 × 44 px, pas de zoom involontaire)
- **Dépend de**: US-001 (socle multi-tenant), US-003 (RBAC/HAB), US-050 (saisie de base)
- **Spec Technique**: EF-TMP-6, ENF-UX-3

## User Story

**En tant que** collaborateur (Camille) en déplacement ou sans accès à un ordinateur,
**je veux** saisir mes imputations de la journée directement depuis mon téléphone avec la même complétude fonctionnelle qu'en version web,
**afin de** ne jamais accumuler de retard de saisie indépendamment de mon contexte de travail.

## Critères d'Acceptation

### CA-1 (Nominal) : Saisie complète d'une journée sur mobile en ≤ 90 secondes

```gherkin
GIVEN Camille ouvre l'application sur son téléphone (iOS 17+ ou Android 14+, viewport 390 × 844 px)
  AND elle est affectée à 3 projets ouverts
  AND la vue quotidienne du jour courant est affichée par défaut au démarrage
WHEN elle saisit ses imputations : 3h sur projet P-Alpha, 3h sur P-Beta, 2h sur P-Gamma
  AND ajoute un commentaire court sur la ligne P-Alpha ("Réunion client")
  AND soumet la journée
THEN les imputations sont enregistrées avec les données exactes
  AND la durée totale de la manipulation est ≤ 90 secondes (mesurée depuis l'ouverture de la vue quotidienne)
  AND aucun zoom involontaire ne s'est déclenché lors de la saisie dans les champs de durée
  AND le clavier numérique natif apparaît automatiquement sur les champs de durée
```

### CA-2 (Alternatif) : Parité fonctionnelle mobile/web — toutes les actions de US-050 disponibles

```gherkin
GIVEN Camille utilise la version mobile de l'application
WHEN elle accède à la vue de saisie quotidienne
THEN elle peut effectuer les actions suivantes sans basculer sur desktop :
  - Choisir le projet dans une liste déroulante filtrée à ses affectations
  - Saisir une durée en heures et demi-journées
  - Ajouter ou modifier un commentaire optionnel
  - Dupliquer le jour précédent
  - Soumettre la journée
  AND chaque zone tactile (bouton, champ, sélecteur) mesure au minimum 44 × 44 px
  AND aucune fonctionnalité de US-050 n'est absente ou masquée derrière un menu inaccessible
```

### CA-3 (Alternatif) : Navigation entre jours de la semaine sur mobile

```gherkin
GIVEN Camille est sur la vue quotidienne du mardi 25/08/2026
WHEN elle effectue un swipe horizontal vers la gauche
THEN la vue du mercredi 26/08/2026 s'affiche avec les imputations correspondantes (vides ou déjà saisies)
  AND le swipe vers la droite revient au mardi 25/08/2026 avec les données intactes
  AND la semaine courante est clairement indiquée (indicateur de position : "Mar 25/08 — semaine 35")
  AND la navigation entre jours ne déclenche aucun rechargement de page complet (transitions ≤ 200 ms)
```

### CA-4 (Erreur) : Perte de connexion pendant la saisie — données non perdues

```gherkin
GIVEN Camille a saisi 4h sur P-Alpha sur mobile
  AND la connexion réseau est interrompue (mode avion, zone sans couverture)
WHEN elle tente de soumettre la journée
THEN l'interface affiche "Connexion indisponible — votre saisie est sauvegardée localement"
  AND les données saisies sont conservées dans le stockage local du navigateur (localStorage)
  AND lorsque la connexion est rétablie, un message "Synchronisation disponible" apparaît et l'envoi reprend en un tap
  AND aucune donnée n'est perdue entre la saisie hors ligne et la synchronisation
```

### CA-5 (Erreur) : Taille d'écran trop petite — dégradation gracieuse sans perte fonctionnelle

```gherkin
GIVEN Camille utilise un téléphone avec un viewport de 320 × 568 px (iPhone SE 1ère gen)
WHEN elle ouvre la vue de saisie quotidienne
THEN l'interface s'adapte sans barre de défilement horizontale (overflow-x: hidden)
  AND tous les éléments interactifs restent visibles et actionnables sans zoom
  AND les champs de saisie ont une hauteur minimale de 44 px
  AND la soumission est possible sans modification de configuration
```

## Critères UI/UX

### Web (tablette ≥ 768 px)
- Sur tablette, la vue hebdomadaire est accessible en mode paysage ; la vue quotidienne reste disponible en mode portrait.
- La navigation swipe est disponible sur écran tactile, même en mode navigateur web.

### Mobile (téléphone < 768 px)
- La vue par défaut est la vue **quotidienne** (EF-TMP-6) — la vue hebdomadaire dense est désactivée pour les viewports < 768 px.
- Le clavier numérique natif (inputmode="decimal") est déclenché sur tous les champs de durée.
- Les zones tactiles respectent les guidelines Apple HIG et Material Design 3 : minimum 44 × 44 pt / 48 × 48 dp.
- La liste de projets utilise un sélecteur natif (select ou bottom-sheet) pour éviter le débordement.
- Le bouton "Soumettre" est positionné en bas de l'écran (thumb zone) et reste visible au-dessus du clavier système.
- Aucune fonctionnalité n'est accessible uniquement par hover (pas de tooltip hover-only).

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

**EF-TMP-6** : la saisie quotidienne mobile doit être fonctionnellement complète — il n'est pas acceptable de renvoyer le collaborateur sur desktop pour effectuer une action disponible en web. La parité fonctionnelle est un critère de recette (CA-2).

**ENF-UX-3** : les tests de conformité mobile doivent couvrir les deux systèmes (iOS Safari et Chrome Android) et au moins deux tailles d'écran représentatives (375 px et 390 px en largeur). Les tests d'accessibilité mobile (VoiceOver et TalkBack) sont requis pour la DoD.

**Stockage offline (CA-4)** : le mécanisme de sauvegarde locale repose sur localStorage ou IndexedDB selon la taille des données. La synchronisation automatique se déclenche dès le retour de la connectivité (événement `online`) sans action supplémentaire de l'utilisateur (le tap unique est une option de confirmation, non une obligation).
