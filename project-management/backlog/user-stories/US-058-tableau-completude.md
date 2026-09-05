# US-058: Tableau de bord de complétude de saisie

## Métadonnées
- **ID**: US-058
- **EPIC**: EPIC-003
- **Sprint**: Sprint 2
- **Statut**: ✅ Done (livré Sprint 5)
- **Points**: 3
- **Persona**: P2 (Marc — chef de projet), P3 (Sophie — directrice de BU / reporting)
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

## Traçabilité
- **Implémente**: EF-TMP-24 (tableau de complétude : taux de saisie par collaborateur et par semaine en une seule vue), OBJ-1 (objectif : saisie complète à J+2 ≥ 90 %)
- **Dépend de**: US-001 (socle multi-tenant), US-003 (RBAC/HAB), US-050 (saisie de base — données de complétude)
- **Spec Technique**: EF-TMP-24, OBJ-1

## User Story

**En tant que** chef de projet (Marc) et directrice de BU (Sophie),
**je veux** consulter un tableau de bord de complétude de saisie présentant en une seule vue le taux de saisie par collaborateur et par semaine avec repérage visuel immédiat des retards,
**afin de** piloter l'objectif OBJ-1 (complétude ≥ 90 % à J+2) et identifier sans délai les collaborateurs nécessitant une relance.

## Critères d'Acceptation

### CA-1 (Nominal) : Vue unique complétude — taux par collaborateur et par semaine, chargement ≤ 3 secondes

```gherkin
GIVEN Marc gère une équipe de 15 collaborateurs sur 3 projets
  AND la vue de complétude couvre les 4 dernières semaines glissantes
  AND les données de saisie sont à jour
WHEN Marc ouvre le tableau de bord "Complétude de saisie"
THEN la vue affiche une grille collaborateurs × semaines avec, pour chaque cellule :
  - ✅ Vert : semaine soumise (100 % des jours ouvrés saisis)
  - ⚠️ Orange : semaine partielle (1 à 99 % des jours ouvrés saisis)
  - ❌ Rouge : semaine vide (0 % saisi, J+2 dépassé)
  - ⏳ Gris : semaine en cours (délai J+2 non atteint)
  AND le taux global de la semaine courante est affiché en en-tête (ex : "Semaine 35 : 87 % complète — 2 collaborateurs en retard")
  AND le temps de chargement de la vue est ≤ 3 secondes sur l'environnement de production
```

### CA-2 (Alternatif) : Identification immédiate des retards et lancement de relance manuelle

```gherkin
GIVEN le tableau de bord affiche 2 collaborateurs avec une cellule rouge pour la semaine 35
WHEN Marc clique sur la cellule rouge de Camille (semaine 35)
THEN une infobulle affiche : "Camille Martin — Semaine 35 : 0h saisies / 40h attendues. Dernière connexion : 27/08/2026"
  AND un bouton "Envoyer une relance maintenant" est disponible dans l'infobulle
  AND si Marc clique "Envoyer une relance maintenant", une relance est envoyée immédiatement à Camille (canal email + in-app)
  AND la relance manuelle est tracée dans le journal de relances (US-056)
  AND la cellule reste rouge jusqu'à la soumission effective par Camille
```

### CA-3 (Alternatif) : Filtrage par projet, BU ou périmètre managérial

```gherkin
GIVEN Sophie dirige une BU composée de 40 collaborateurs répartis sur 10 projets
WHEN elle applique le filtre "BU = Business Intelligence" dans le tableau de complétude
THEN la vue affiche uniquement les 40 collaborateurs de sa BU
  AND le taux global est recalculé pour ce périmètre
  AND elle peut exporter la vue filtrée au format CSV avec les colonnes : Collaborateur, Semaine, Taux %, Statut, Dernière action
  AND l'export respecte le périmètre RBAC de Sophie (elle ne voit pas les BU dont elle n'a pas la responsabilité)
```

### CA-4 (Erreur) : Vue vide si aucune saisie n'existe — message explicite sans erreur technique

```gherkin
GIVEN un nouveau tenant vient d'être créé et aucun collaborateur n'a encore saisi d'imputation
WHEN Marc ouvre le tableau de bord de complétude
THEN la vue affiche un message "Aucune donnée de saisie disponible pour votre périmètre. Les premières saisies apparaîtront ici dès que vos collaborateurs auront soumis leurs imputations."
  AND aucune erreur technique (500, NaN, division par zéro) n'est affichée
  AND la grille est vide mais structurée (en-têtes présents) pour illustrer la disposition attendue
```

### CA-5 (Erreur) : Accès par un collaborateur non habilité — vue limitée à soi-même

```gherkin
GIVEN Camille possède uniquement le rôle "Collaborateur"
WHEN elle accède à la vue "Complétude de saisie"
THEN elle voit uniquement ses propres données de complétude (ses semaines, son taux)
  AND les données des autres collaborateurs ne sont pas affichées ni accessibles via l'API
  AND l'API /api/completude?scope=team retourne HTTP 403 Forbidden si appelée avec le JWT de Camille
  AND sa propre vue de complétude s'affiche normalement (droit de consultation sur soi-même)
```

## Critères UI/UX

### Web
- La grille collaborateurs × semaines utilise un code couleur à 4 états (vert, orange, rouge, gris) avec une légende permanente visible.
- Le taux global de la semaine est affiché en chiffre clé mis en valeur (grand caractère, position haute de la page).
- L'infobulle de cellule (CA-2) s'affiche au survol (hover) et au clic, contenant le détail et l'action de relance directe.
- La vue est paginée ou virtualisée pour les grandes équipes (> 50 collaborateurs) sans dégradation de performance.
- Un sélecteur de période permet de naviguer entre les semaines (flèches gauche/droite ou calendrier).

### Mobile
- Sur mobile, la grille est remplacée par une liste triée par statut (retards en tête) avec un indicateur coloré par ligne.
- Le tableau complet (grille) est accessible via un bouton "Vue tableau" qui bascule en orientation paysage.
- L'action "Relancer maintenant" est disponible en swipe ou via menu contextuel sur chaque ligne de collaborateur en retard.

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

**OBJ-1** : cet écran est l'outil principal de pilotage de l'objectif OBJ-1 (complétude ≥ 90 % à J+2). Le taux est calculé comme suit : nombre de semaines × collaborateurs entièrement soumises à J+2 / nombre total de semaines × collaborateurs attendues. Le J+2 est calculé en jours ouvrés (hors weekends et jours fériés du tenant).

**EF-TMP-24** : la vue "en une seule page" signifie qu'aucune navigation supplémentaire n'est nécessaire pour obtenir l'image globale de la semaine. Les détails (infobulle, relance) sont accessibles par interaction sans changer de page.

**Performance** : le calcul du taux de complétude peut nécessiter une vue matérialisée ou un cache applicatif (rafraîchi toutes les 15 minutes) pour les grandes équipes. Le temps de chargement de ≤ 3 s est mesuré sur un tenant de 100 collaborateurs × 8 semaines en staging.

**Export CSV** : l'export respecte le périmètre RBAC et est protégé contre l'injection CSV (pas de formules déguisées dans les champs texte).
