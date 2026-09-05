# US-051: Saisie d'une semaine nominale en ≤ 2 minutes (critère bloquant)

## Métadonnées
- **ID**: US-051
- **EPIC**: EPIC-003
- **Sprint**: Sprint 1
- **Statut**: ✅ Done (livré Sprint 3)
- **Points**: 8
- **Persona**: P1 (Camille — collaborateur)
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: EF-TMP-3 (saisie d'une semaine complète de 3 à 5 projets + absences éventuelles en ≤ 2 minutes pour un utilisateur formé), ENF-UX-1 (fluidité de navigation : aucune rupture de flux involontaire), ENF-UX-2 (accessibilité clavier complète de la vue de saisie)
- **Dépend de**: US-001 (socle multi-tenant), US-003 (RBAC/HAB), US-050 (saisie de base)
- **Spec Technique**: EF-TMP-3, ENF-UX-1, ENF-UX-2

## User Story

**En tant que** collaborateur (Camille),
**je veux** pouvoir saisir l'intégralité de ma semaine de travail (3 à 5 projets, absences incluses) en moins de 2 minutes,
**afin que** la saisie du temps ne soit pas perçue comme une contrainte administrative lourde et que le taux d'adoption soit maximal dès le premier sprint.

## Critères d'Acceptation

### CA-1 (Nominal) : Semaine 5 projets saisie en ≤ 2 minutes par 5 collaborateurs représentatifs

```gherkin
GIVEN 5 collaborateurs représentatifs (profils variés : junior, senior, itinérant, sédentaire, partiellement absents) ont reçu une formation de 15 minutes à l'outil
  AND chacun dispose d'une semaine test comportant 5 projets ouverts affectés et 1 demi-journée d'absence
  AND le chronomètre démarre à l'ouverture de la vue de saisie hebdomadaire
WHEN chaque collaborateur saisit les 5 imputations (5 × 8 h = 40 h réparties sur 5 jours) et l'absence
  AND soumet la semaine
THEN 100 % des 5 collaborateurs (5/5) terminent la saisie en ≤ 120 secondes
  AND les données enregistrées sont exactes (aucune erreur de projet, durée ou date)
  AND le résultat de ce test est consigné dans le rapport de recette du lot 1 avec chronomètre par participant
```

### CA-2 (Alternatif) : Autonomie sans formation — test novice

```gherkin
GIVEN 3 collaborateurs n'ayant reçu aucune formation préalable ont accès à l'interface
  AND chacun dispose d'une semaine test comportant 3 projets ouverts affectés
  AND le chronomètre démarre à l'ouverture de la vue de saisie
WHEN chaque collaborateur tente de saisir ses 3 imputations journalières sans assistance
THEN au moins 2 collaborateurs sur 3 (66 %) terminent la saisie en ≤ 3 minutes sans aide extérieure
  AND aucun blocage (erreur fatale, page blanche, formulaire non soumettable) n'est rencontré
  AND les éventuels irritants relevés sont consignés dans le backlog ergonomique pour itération
```

### CA-3 (Alternatif) : Navigation entièrement au clavier sans usage de la souris

```gherkin
GIVEN un collaborateur utilise exclusivement le clavier (Tab, Shift+Tab, Entrée, touches fléchées)
  AND la semaine test comporte 4 projets affectés
WHEN il navigue entre les cellules de la grille hebdomadaire et saisit les durées au clavier
  AND il soumet la semaine avec la touche Entrée sur le bouton de validation
THEN chaque cellule de la grille est atteignable par Tab sans détour
  AND la saisie est complète en ≤ 2 minutes (temps mesuré)
  AND aucun piège de focus (focus perdu, retour en début de page) n'est détecté
```

### CA-4 (Erreur) : Mesure > 2 minutes — déclenchement d'itération ergonomique obligatoire

```gherkin
GIVEN le test utilisateur décrit en CA-1 est exécuté en recette
WHEN au moins 1 collaborateur sur 5 dépasse 120 secondes pour la saisie de sa semaine nominale
THEN la story US-051 est marquée "ÉCHEC" dans le rapport de recette du lot 1
  AND une itération ergonomique est ouverte comme story bloquante avant la livraison du lot 1
  AND les points de friction identifiés (clics superflus, champs trop petits, latence réseau > 300 ms…) sont documentés avec vidéo ou capture
  AND aucune autre story du lot 1 ne peut être déclarée "Done" tant qu'US-051 n'est pas validée
```

### CA-5 (Erreur) : Latence réseau dégradée — saisie toujours fonctionnelle

```gherkin
GIVEN la connexion réseau est dégradée à 3G simulée (≈ 1 Mbps, latence 200 ms)
  AND le collaborateur saisit une semaine de 3 projets
WHEN il soumet la saisie
THEN la soumission aboutit sans perte de données en ≤ 10 secondes
  AND un indicateur de chargement est visible pendant l'attente
  AND si la soumission dépasse 10 s, un message "Connexion lente — votre saisie est en cours d'enregistrement" est affiché
  AND les données saisies localement ne sont pas effacées en cas de timeout réseau (mécanisme de reprise)
```

## Critères UI/UX

### Web
- La vue hebdomadaire est conçue avec une grille dense (projection type tableur) permettant la saisie sans navigation de page.
- La tabulation entre cellules suit l'ordre naturel : jour × projet, de gauche à droite et de haut en bas.
- Les actions principales (dupliquer, soumettre) sont accessibles par raccourci clavier documenté dans l'interface (tooltip au survol).
- Le bouton de soumission est visible sans scroll sur les résolutions ≥ 1280 × 768 px.

### Mobile
- Sur mobile, l'objectif des 2 minutes est mesuré sur la vue quotidienne (US-052) ; la vue hebdomadaire dense est réservée aux tablettes (≥ 768 px).
- La vue mobile optimise le nombre de taps : saisie en un tap par cellule, clavier numérique natif déclenché automatiquement.

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

**CRITÈRE BLOQUANT DE RECETTE LOT 1** : cette User Story constitue le critère de qualité transverse de l'ensemble du module TMP. Son échec bloque la livraison du lot 1, indépendamment de l'état des autres stories. Le test utilisateur (CA-1) doit être conduit avec des collaborateurs réels, non avec des membres de l'équipe de développement.

**Méthode de mesure** : chronomètre externe (application tierce ou observateur dédié) démarrant à l'apparition de la vue de saisie et stoppant à la confirmation serveur de la soumission. La vidéo de chaque test est archivée comme preuve de recette.

**Note sur l'objectif** : cette story n'est pas une implémentation technique au sens strict — elle est un objectif d'ergonomie mesurable. L'équipe doit tester et itérer jusqu'à atteindre le seuil de 2 minutes sur US-050 + US-052 + US-053 combinés. Un score > 120 s implique une revue de l'expérience utilisateur (réduction des clics, saisie anticipée, raccourcis clavier, préremplissage IA via US-053).
