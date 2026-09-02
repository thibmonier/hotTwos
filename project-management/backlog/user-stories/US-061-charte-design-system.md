# US-061: Charte et design system (design.md → tokens + composants Skote)

## Métadonnées
- **ID**: US-061
- **EPIC**: EPIC-012
- **Sprint**: À planifier (fast-track)
- **Statut**: 🔴 To Do
- **Points**: 5
- **Persona**: Tous (P1 à P6) — bénéficiaires d'une interface cohérente ; conçue avec l'équipe UX/UI
- **Créé le**: 2026-09-02
- **Mis à jour**: 2026-09-02

## Traçabilité
- **Implémente**: EPIC-012 (MMF — design system posé), OBJ-7 (adoption), RSQ-1 (résistance à la saisie)
- **Dépend de**: EPIC-000 (socle Twig/Stimulus, AssetMapper), thème Skote (`project-management/Skote_Symfony_v2.2.0/`), `design.md` (claude-design)
- **Prérequis de**: US-063 (intégration layout), US-064 (reskin des écrans)
- **Spec Technique**: `design.md`, charte visuelle

## User Story

**En tant que** équipe produit au service de tous les utilisateurs (collaborateur, chef de projet, resource manager, direction),
**je veux** disposer d'une charte et d'un design system documenté (tokens de couleurs, typographie, espacements, et composants réutilisables — boutons, tables, badges, formulaires, drawer — dérivés du thème Skote et du `design.md`),
**afin de** poser une base visuelle cohérente et faisant autorité, réutilisable par tous les écrans existants et à venir, condition de l'adoption du produit (OBJ-7).

## Critères d'Acceptation

### CA-1 (Nominal) : Design system documenté et faisant autorité

```gherkin
GIVEN le thème Skote et le brief design.md sont disponibles
WHEN l'équipe formalise le design system
THEN un document de charte (design.md faisant autorité) définit :
  - la palette de couleurs (primaire, secondaire, sémantiques succès/alerte/erreur/info, neutres) sous forme de tokens
  - l'échelle typographique (familles, tailles, graisses, interlignes)
  - l'échelle d'espacements et le système de grille
  - les composants de base : boutons (variantes, états), tables, badges/pastilles de statut, champs de formulaire, drawer/panneau latéral
  AND chaque token est nommé de façon sémantique (ex : --color-status-danger, pas --red-500 en usage)
  AND le document est référencé comme source unique par les écrans (pas de valeurs de style en dur dispersées)
```

### CA-2 (Alternatif) : Tokens exploitables dans le pipeline d'assets

```gherkin
GIVEN le design system est formalisé
WHEN les tokens sont intégrés au pipeline AssetMapper
THEN les couleurs, typographies et espacements sont exposés en variables CSS (custom properties) réutilisables
  AND un thème clair et un thème sombre partagent la même structure de tokens (valeurs distinctes, mêmes noms)
  AND un écran de démonstration (styleguide) présente chaque composant dans ses variantes et états
```

### CA-3 (Alternatif) : Continuité avec la base UI existante

```gherkin
GIVEN la base sobre a déjà été posée (PR #11 — retrait du placeholder skyblue, fond #f6f7f9)
WHEN le design system est appliqué
THEN la base existante est absorbée/rationalisée dans les tokens (pas de double source de vérité)
  AND aucune régression de fond global n'est introduite
  AND la migration des valeurs actuelles vers les tokens est tracée
```

### CA-4 (Erreur) : Cohérence des couleurs sémantiques vérifiée

```gherkin
GIVEN les codes couleur de statut sont déjà utilisés dans les écrans (complétude : vert/orange/rouge/gris)
WHEN les tokens sémantiques sont définis
THEN les états existants sont mappés sur les tokens sémantiques (succès, avertissement, erreur, neutre)
  AND aucune couleur de statut n'est laissée hors du système (pas de valeur hexadécimale orpheline)
  AND un contrôle (revue ou lint de style) échoue si une couleur brute est utilisée hors token
```

### CA-5 (Erreur) : Accessibilité des tokens dès la charte

```gherkin
GIVEN les tokens de couleurs sont proposés
WHEN les contrastes texte/fond sont vérifiés
THEN chaque paire texte/fond des composants atteint au minimum le ratio WCAG 2.2 AA (4.5:1 texte normal, 3:1 texte large)
  AND toute paire non conforme est corrigée avant validation de la charte
  AND le résultat de la vérification de contraste est documenté dans la charte
```

## Critères UI/UX

### Web
- Un styleguide (page de démonstration) présente tokens et composants dans toutes leurs variantes et états (hover, focus, disabled, actif).
- Le thème clair/sombre est démontrable par bascule sur le styleguide.
- Les composants sont pensés responsive (mobile ↔ desktop) dès la charte.

### Mobile
- Les cibles tactiles des composants interactifs respectent une taille minimale de 44 × 44 px (cf. F-S5-1).
- Aucun élément d'interaction n'est accessible uniquement au survol (parité tactile).

## Tasks

| ID | Type | Description | Statut | Estimation |
|----|------|-------------|--------|------------|
| - | - | - | 🔴 | - |

## Progression

0/0 tasks complétées (0%)

## Definition of Done

- [ ] Tous les critères d'acceptation validés
- [ ] Design system documenté (charte + styleguide)
- [ ] Tokens intégrés au pipeline d'assets (clair/sombre)
- [ ] Contrastes WCAG 2.2 AA vérifiés
- [ ] `make ci` vert (pas de régression)

---

## Notes

**Fast-track** : cette US est la fondation d'EPIC-012 (D1). Elle doit être livrée tôt pour débloquer l'intégration du layout (US-063) et le reskin (US-064).

**Sources** : thème Skote `project-management/Skote_Symfony_v2.2.0/` (Admin / Documentation / Starterkit) ; `design.md` (claude-design) ; consigne PO « conception UX/UI avant dev front ».

**Périmètre** : poser la charte et les composants, pas reskiner les écrans (D4). Le styleguide sert de contrat visuel validé avant intégration.
