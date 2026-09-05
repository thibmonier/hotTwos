# US-055: Validation des temps par lot

## Métadonnées
- **ID**: US-055
- **EPIC**: EPIC-003
- **Sprint**: Sprint 2
- **Statut**: ✅ Done (livré Sprint 3)
- **Points**: 5
- **Persona**: P2 (Marc — chef de projet / valideur)
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: EF-TMP-20 (validation ou refus par lot avec motif de refus obligatoire ; validation d'une semaine de 10 collaborateurs en moins de 5 minutes)
- **Dépend de**: US-001 (socle multi-tenant), US-003 (RBAC/HAB), US-050 (saisie de base — données à valider)
- **Spec Technique**: EF-TMP-20

## User Story

**En tant que** chef de projet (Marc),
**je veux** valider ou refuser les imputations de temps de mes collaborateurs par lot (sélection multiple, action en masse) avec un motif obligatoire en cas de refus,
**afin de** gérer la validation hebdomadaire de mon équipe en moins de 5 minutes sans validation individuelle ligne par ligne.

## Critères d'Acceptation

### CA-1 (Nominal) : Validation d'une semaine de 10 collaborateurs en moins de 5 minutes

```gherkin
GIVEN Marc est chef de projet sur 10 collaborateurs affectés à ses projets
  AND les 10 collaborateurs ont soumis leurs imputations de la semaine 35 (24-28/08/2026)
  AND Marc accède à la vue "Validation des temps — Semaine 35"
WHEN il sélectionne tous les collaborateurs via la case "Tout sélectionner"
  AND clique sur "Valider la sélection"
  AND confirme dans la boîte de dialogue de validation en masse
THEN les imputations des 10 collaborateurs sont validées simultanément
  AND chaque collaborateur reçoit une notification "Vos imputations de la semaine 35 ont été validées"
  AND la durée totale de l'opération (depuis l'ouverture de la vue jusqu'à la confirmation) est ≤ 5 minutes, mesurée lors des tests de recette
  AND les imputations passent au statut "Validé" et deviennent éligibles à la valorisation (US-060)
```

### CA-2 (Alternatif) : Refus partiel avec motif — un collaborateur sur la sélection

```gherkin
GIVEN Marc visualise les imputations de la semaine 35 de 10 collaborateurs
  AND les imputations de Camille montrent 12h imputées sur P-Alpha le mercredi (dépassement inhabituel)
WHEN Marc désélectionne Camille de la validation en masse
  AND sélectionne uniquement Camille et clique "Refuser la sélection"
  AND saisit le motif "12h sur P-Alpha le 26/08 — veuillez justifier ou corriger" (255 caractères max)
  AND confirme le refus
THEN les imputations de Camille sont refusées avec le statut "Refusé"
  AND Camille reçoit une notification "Vos imputations de la semaine 35 ont été refusées. Motif : 12h sur P-Alpha le 26/08 — veuillez justifier ou corriger"
  AND les imputations des 9 autres collaborateurs restent validées
  AND Camille peut modifier et resoumettre ses imputations
```

### CA-3 (Alternatif) : Filtrage par projet et période avant validation en masse

```gherkin
GIVEN Marc gère 3 projets (P-Alpha, P-Beta, P-Gamma) avec des équipes différentes
  AND il veut valider uniquement les imputations sur P-Alpha pour la semaine 35
WHEN il applique le filtre "Projet = P-Alpha" et "Période = Semaine 35"
THEN la liste affiche uniquement les collaborateurs ayant imputé sur P-Alpha la semaine 35
  AND l'action "Tout sélectionner" ne sélectionne que ces collaborateurs
  AND la validation en masse ne touche pas les imputations sur P-Beta et P-Gamma
  AND le filtre actif est clairement indiqué dans l'en-tête de la vue ("Filtré : P-Alpha — Sem. 35")
```

### CA-4 (Erreur) : Refus sans motif — soumission bloquée

```gherkin
GIVEN Marc a sélectionné les imputations de 3 collaborateurs pour les refuser
WHEN il clique "Refuser la sélection" et laisse le champ "Motif" vide
  AND tente de confirmer le refus
THEN la boîte de dialogue affiche "Le motif de refus est obligatoire (1 à 255 caractères)"
  AND le refus n'est pas exécuté
  AND les imputations restent au statut "Soumis" (inchangé)
  AND le focus est placé automatiquement dans le champ "Motif" pour guider Marc
```

### CA-5 (Erreur) : Tentative de validation par un collaborateur non habilité

```gherkin
GIVEN Camille possède uniquement le rôle "Collaborateur" (pas de rôle "Valideur" ni "Chef de projet")
WHEN elle tente d'accéder à la vue "Validation des temps" via l'URL directe /time-validation
  AND l'API reçoit une requête POST /api/timesheets/batch-validate avec le JWT de Camille
THEN l'interface retourne une redirection vers la vue de saisie collaborateur (pas d'accès à la validation)
  AND l'API retourne HTTP 403 Forbidden avec le message "Rôle 'valideur' requis pour cette action"
  AND aucune imputation n'est modifiée
  AND un événement "unauthorized_validation_attempt" est loggé
```

## Critères UI/UX

### Web
- La vue de validation est une liste paginée (50 lignes par page) avec cases à cocher pour la sélection individuelle et une case "Tout sélectionner/désélectionner" en en-tête.
- Les actions "Valider" et "Refuser" sont des boutons distincts, colorés (vert/rouge), désactivés tant qu'aucun élément n'est sélectionné.
- La colonne "Total semaine" affiche la somme des heures par collaborateur pour permettre une détection visuelle rapide des anomalies.
- Le motif de refus est saisi dans une modale inline (pas de nouvelle page) avec compteur de caractères restants.
- Un indicateur de progression (spinner, message "Validation en cours…") est affiché pendant le traitement d'un lot volumineux.

### Mobile
- Sur mobile, la validation en masse est accessible en mode simplifié : liste des collaborateurs avec swipe vers la droite pour valider et vers la gauche pour refuser (avec confirmation tactile).
- Le motif de refus s'ouvre dans un bottom-sheet avec champ textuel et clavier plein écran.
- La validation individuelle (un collaborateur à la fois) est priorisée sur mobile pour éviter les sélections accidentelles.

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

**EF-TMP-20 — Critère de performance** : la validation d'une semaine de 10 collaborateurs en ≤ 5 minutes inclut le temps de chargement de la vue, la sélection et la confirmation. Un test de performance avec données réelles (10 collaborateurs × 5 jours × 3 projets = 150 imputations) doit être exécuté en recette sur l'environnement de staging.

**Transitions de statut** : le cycle de vie d'une imputation est : BROUILLON → SOUMIS → VALIDÉ | REFUSÉ. Seules les imputations au statut VALIDÉ alimentent la valorisation (US-060). Un refus remet l'imputation au statut REFUSÉ et notifie le collaborateur, qui peut corriger et resoumettre.

**Habilitations** : seul un collaborateur possédant le rôle "Valideur temps" sur un projet donné peut valider les imputations sur ce projet. Le périmètre de validation est filtré à la source : Marc ne voit que les collaborateurs affectés à ses projets.
