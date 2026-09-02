# ADR-0018 — Intégration du design system (Skote) en CSS compilé + tokens, sans build Sass

- **Statut :** Adopté (2026-09-02)
- **Réf. CDC :** précise ADR-0005 (présentation Twig + Stimulus + Turbo, rendu serveur) ; sert EPIC-012 (design & ergonomie définitive)
- **Portée :** EPIC-012 — US-061 (charte & design system), US-063 (intégration du layout), US-064 (reskin)

## Contexte

Jusqu'au lot 1, les écrans ont été construits en « walking skeleton » : HTML sémantique, mobile-first, accessible, mais **sans design définitif** (CSS ad hoc dans `assets/styles/app.css`). La recette des Sprints 5/6 a confirmé qu'aucune intégration de design n'était planifiée (placeholder `skyblue` du scaffold — F-S5-3, corrigé), et a relevé des écarts d'ergonomie/lisibilité (F-S5-4, F-S5-5).

EPIC-012 pose le design définitif à partir du **thème Skote** (`project-management/Skote_Symfony_v2.2.0/`), un template d'administration **Bootstrap 5** distribué sous forme de **sources SCSS** (`assets/scss/_variables.scss`, `_variables-dark.scss`, `bootstrap.scss`, `app.scss`).

Or le socle de présentation réel repose sur **Symfony AssetMapper** (importmap `importmap.php`, `assets/vendor/`, CSS servi tel quel), **sans étape de compilation** Sass/npm. C'est une réalisation qui **précise ADR-0005** : le CDC évoquait « Symfony Reprise + Vite », mais la construction s'est faite sur AssetMapper, dont l'atout est justement l'absence de build front (cf. PR #10 — recompilation des assets en dev, PR #11 — base UI sobre).

La question tranchée : **comment intégrer un thème Bootstrap/SCSS sur un pipeline sans build Sass**, sans introduire de dette d'outillage ni de régression.

## Décision

1. **Intégration en CSS compilé + couche de tokens en CSS custom properties, sans build Sass.**
   - On consomme la feuille **Bootstrap 5 / Skote déjà compilée** (CSS statique versionné dans `assets/`), servie par AssetMapper. **Aucune compilation Sass**, aucun ajout de Vite/npm au pipeline applicatif.
   - Au-dessus, une **couche de tokens** en variables CSS (`--color-*`, `--space-*`, `--radius-*`, `--font-*`) constitue la **source de personnalisation**. Les valeurs des tokens sont **dérivées** des variables Skote (`_variables.scss` / `_variables-dark.scss`) mais ré-exprimées en custom properties.
   - La charte (`project-management/architecture/design-system.md`, US-061) est la **source de vérité** des tokens et des composants ; le SCSS Skote sert de référence de dérivation, pas de code de production.

2. **Thème clair/sombre par tokens.** Les tokens sont définis sur `:root` (clair), redéfinis sous `@media (prefers-color-scheme: dark)` et surchargés par `[data-theme="dark"|"light"]` (choix explicite prioritaire). Un même nom de token, deux jeux de valeurs. Aucun composant ne définit une couleur en dur hors token.

3. **Réconciliation du CSS existant.** Les conventions déjà en place dans `app.css` (focus visible `outline` — WCAG 2.4.7, cibles tactiles ≥ 44 px, `.visually-hidden`, badges de statut projet, statuts de complétude) sont **migrées vers les tokens** sans changement de sens. Règle projet maintenue : **l'information n'est jamais portée par la seule couleur** (WCAG 1.4.1) — toujours doublée d'un libellé/icône.

4. **Non-régression comme critère bloquant.** Toute intégration (layout US-063, reskin US-064) conserve les critères d'acceptation fonctionnels des US d'origine ; `make ci` reste vert et la recette navigateur est re-passée. Le reskin d'un écran est conditionné à la **validation de sa maquette** (US-062, consigne PO « UX/UI avant dev front »).

5. **Budget d'assets maîtrisé.** On n'embarque que le CSS nécessaire (pas l'intégralité des libs de démonstration Skote). Objectif : pas de dégradation du temps de chargement (NFR EPIC-012).

## Conséquences

### Positives
- **Zéro build front** : cohérent avec AssetMapper, pas de rupture d'outillage ni de complexité CI/Docker supplémentaire.
- **Personnalisation par tokens** : thème clair/sombre et évolutions de charte centralisés, réutilisables par tous les écrans et les lots suivants.
- **Look Skote conservé** (Bootstrap compilé) sans dépendre du pipeline Sass du thème.
- **Migration progressive** : les écrans passent au design system un par un (après maquette validée), sans big-bang.

### Négatives / points de vigilance
- **Personnalisation Sass complète non disponible** : on ne recompile pas Bootstrap depuis ses variables Sass. Toute personnalisation profonde de Bootstrap au-delà de ce que les custom properties permettent est hors périmètre (accepté : la couche de tokens couvre les besoins de charte ; une bascule vers un build Sass ferait l'objet d'un nouvel ADR).
- **Double référentiel de valeurs** : les tokens (production) dérivent des variables SCSS Skote (référence). La correspondance doit être **relue** (pas de génération automatique) — tracée dans la note de migration de la charte.
- **Poids du CSS Bootstrap** : à surveiller (purge/sous-ensemble si nécessaire) pour tenir le budget d'assets.
- **Bootstrap 5 en CSS uniquement** : les composants Skote nécessitant du JS (drawer, dropdowns) sont ré-implémentés ou pilotés via **Stimulus** (ADR-0005), pas via le bundle JS de Bootstrap, pour éviter d'introduire une seconde brique JS.

## Alternatives écartées

| Alternative | Raison de l'écart |
|-------------|-------------------|
| **Pipeline Sass (recompiler Skote depuis les SCSS)** | Introduit un build step (sass bundle ou npm), en rupture avec la logique no-build d'AssetMapper ; complexité CI/Docker et dette d'outillage non justifiées au regard du besoin (charte + reskin du lot 1). |
| **Extraction sur-mesure (tokens seuls, composants maison sans Bootstrap)** | Le plus léger, mais coût de ré-implémentation des composants élevé et éloignement du « layout Skote clé en main » recherché. Réservé si le budget d'assets Bootstrap devenait problématique. |

## Suite

- **US-061** produit la charte (`design-system.md`) : tokens clair/sombre, inventaire des composants, vérification des contrastes WCAG 2.2 AA.
- **US-063** intègre le layout (navigation Skote) dans `templates/base.html.twig` via la couche de tokens, sans régression, navigation filtrée par habilitation (parité front/serveur).
- **US-064** reskin les écrans du lot 1 selon les maquettes validées (US-062), absorbe F-S5-4 / F-S5-5.
- **US-065** audite l'accessibilité (WCAG 2.2 AA, `axe-core`).
