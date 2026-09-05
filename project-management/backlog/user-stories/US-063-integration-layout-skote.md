# US-063: Intégration du layout Skote sur le socle Twig/Stimulus

## Métadonnées
- **ID**: US-063
- **EPIC**: EPIC-012
- **Sprint**: À planifier (fast-track)
- **Statut**: ✅ Done (livré Sprint 7)
- **Points**: 5
- **Persona**: Tous (P1 à P6) — navigation et cadre commun à tous les écrans
- **Créé le**: 2026-09-02
- **Mis à jour**: 2026-09-02

## Traçabilité
- **Implémente**: EPIC-012 (D3 — layout intégré), OBJ-7 (adoption — cohérence multi-écrans)
- **Dépend de**: US-061 (charte & design system), EPIC-000 (base.html.twig, AssetMapper, Stimulus)
- **Prérequis de**: US-064 (reskin des écrans dans le nouveau layout)
- **Spec Technique**: thème Skote, `base.html.twig`

## User Story

**En tant que** utilisateur de tous les rôles,
**je veux** un layout applicatif cohérent (navigation, en-tête, structure de page, responsive, thème clair/sombre) dérivé du thème Skote et intégré au socle Twig/Stimulus,
**afin de** naviguer dans une interface homogène sur tous les écrans, sans régression fonctionnelle, et de disposer d'un cadre stable pour greffer chaque écran.

## Critères d'Acceptation

### CA-1 (Nominal) : Layout de base intégré sans régression

```gherkin
GIVEN la charte (US-061) est disponible et le layout Skote validé en maquette (US-062)
WHEN le layout est intégré dans base.html.twig
THEN toutes les pages héritent d'une structure commune : en-tête, navigation principale, zone de contenu, pied
  AND la navigation reflète les modules accessibles selon les habilitations de l'utilisateur (RBAC)
  AND aucun écran existant ne présente de régression fonctionnelle (make ci vert, recette navigateur re-passée)
  AND les assets (CSS/JS) sont servis via le pipeline AssetMapper existant
```

### CA-2 (Alternatif) : Responsive desktop ↔ mobile

```gherkin
GIVEN le layout intègre une navigation
WHEN l'utilisateur consulte l'application sur mobile puis desktop
THEN la navigation s'adapte (menu repliable/hors-écran sur mobile, barre latérale ou horizontale sur desktop)
  AND la zone de contenu reste lisible et exploitable sans défilement horizontal
  AND les cibles tactiles de la navigation respectent 44 × 44 px minimum
```

### CA-3 (Alternatif) : Thème clair/sombre respecté

```gherkin
GIVEN les tokens définissent un thème clair et un thème sombre (US-061)
WHEN le thème est appliqué au layout
THEN le layout et la navigation respectent le thème actif
  AND la préférence de thème est cohérente sur toute l'application (pas d'écran hors thème)
  AND les contrastes restent conformes WCAG 2.2 AA dans les deux thèmes
```

### CA-4 (Erreur) : Navigation filtrée par habilitation

```gherkin
GIVEN Camille possède uniquement le rôle Collaborateur
WHEN elle consulte la navigation principale
THEN seuls les modules auxquels elle a droit sont présentés (pas d'entrée vers coût/marge, administration, etc.)
  AND un accès direct par URL à un module non autorisé reste protégé côté serveur (403), le layout ne contourne aucune autorisation
```

### CA-5 (Erreur) : Chargement des assets robuste

```gherkin
GIVEN le layout charge la CSS et le JS du design system
WHEN une page est servie en APP_ENV=dev puis en APP_ENV=prod
THEN les assets se chargent correctement dans les deux environnements (cf. PR #10 — recompilation dev)
  AND aucune erreur console bloquante n'est présente
  AND l'absence d'un asset optionnel ne casse pas le rendu de la page (dégradation gracieuse)
```

## Critères UI/UX

### Web
- En-tête avec identité applicative, accès au profil et bascule de thème.
- Navigation principale cohérente, état actif visible, focus clavier visible.
- Structure de page réutilisable (titre, fil d'Ariane si pertinent, zone d'actions).

### Mobile
- Navigation repliable (menu hamburger ou drawer) accessible au tactile.
- Contenu prioritaire visible sans défilement horizontal.

## Tasks

| ID | Type | Description | Statut | Estimation |
|----|------|-------------|--------|------------|
| - | - | - | 🔴 | - |

## Progression

0/0 tasks complétées (0%)

## Definition of Done

- [ ] Tous les critères d'acceptation validés
- [ ] Layout intégré dans base.html.twig sans régression (make ci vert)
- [ ] Responsive desktop/mobile vérifié
- [ ] Thème clair/sombre respecté, contrastes AA
- [ ] Navigation filtrée par RBAC (parité front/serveur)
- [ ] Recette navigateur re-passée

---

## Notes

**Prérequis** : US-061 (tokens/composants) et US-062 (maquette du layout validée). L'intégration ne démarre qu'après validation de la maquette (consigne PO).

**Périmètre** : le cadre (layout + navigation), pas le reskin du contenu de chaque écran (US-064). L'objectif est un « terrain stable » sur lequel greffer les écrans.

**Robustesse assets** : tenir compte des correctifs PR #10 (recompilation dev) et PR #11 (base sobre) — le design system remplace ces bases.
