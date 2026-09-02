# ADR-0019 — Intégration front : Tailwind CSS v4 via AssetMapper (sans Node.js), TailAdmin ; supersede ADR-0018

- **Statut :** Adopté (2026-09-02) — **supersede [ADR-0018](./0018-integration-design-system-skote.md)**
- **Réf. CDC :** précise ADR-0005 (présentation Twig + Stimulus + Turbo, rendu serveur, AssetMapper)
- **Portée :** EPIC-012 (design & ergonomie) — refonte de la fondation front avant d'étendre aux lots 2→5

## Contexte

ADR-0018 avait choisi **Bootstrap précompilé + une couche de tokens CSS custom properties, sans build**,
**uniquement** parce qu'AssetMapper n'offre pas d'étape de compilation (donc pas de Sass). Ce choix a
révélé, à l'usage (US-061/063/064/065), plusieurs limites de fond :

- **Poids** : `bootstrap.min.css` **305 KB vendorisé, sans purge** (l'essentiel inutilisé), qui grossit.
- **Deux systèmes en tension** : classes Bootstrap **+** tokens custom properties par-dessus ; la
  conformité contraste (US-065) a dû **overrider Bootstrap élément par élément** (`.btn-primary`, `<code>`…)
  — symptôme d'un framework qu'on combat.
- **Personnalisation bloquée** : impossible de recompiler les variables Sass de Bootstrap.
- **Licence** : le thème **Skote (ThemeForest)** est un risque bloquant non résolu pour un produit SaaS.

Le bundle **`symfonycasts/tailwind-bundle`** lève la prémisse d'ADR-0018 : il embarque un **binaire Tailwind
autonome** (téléchargé et mis en cache, **aucun Node.js**), buildé en JIT (purge), et s'intègre nativement
à AssetMapper. Un **spike** (branche `feature/tailwind-migration`) l'a confirmé.

## Spike — résultats (2026-09-02)

| Point | Mesure |
|-------|--------|
| Build en Docker sans Node.js | ✅ binaire v4.3.3 auto-téléchargé + mis en cache, build **192 ms** |
| Poids du CSS | ✅ **17 KB purgé** (écran complétude) vs **305 KB** Bootstrap — ~18× |
| Config = design system | ✅ Tailwind v4 **CSS-first** (`@theme`) avec nos tokens contraste-vérifiés (US-065) |
| Intégration AssetMapper | ✅ swap + fingerprint (`tailwind-*.css`) |
| Accessibilité | ✅ **0 violation axe-core** (tokens accessibles posés une fois) |
| Licence TailAdmin (version gratuite) | ✅ **MIT** — usage commercial + SaaS autorisé |

## Décision

1. **Tailwind CSS v4 comme socle de style, via `symfonycasts/tailwind-bundle`** — binaire autonome, **sans
   Node.js**, intégré à **AssetMapper** (conforme à l'esprit d'ADR-0005). Version du binaire **pinée**
   (`binary_version`).
2. **Le design system est déclaré en CSS-first** (`@theme` dans l'input Tailwind) : couleurs (valeurs
   accessibles WCAG 2.2 AA d'US-065), typographie (Poppins), espacements, rayons, thème clair/sombre.
   La charte `architecture/design-system.md` reste la source de vérité ; ses valeurs alimentent `@theme`.
3. **Composants issus de TailAdmin** (version HTML/Tailwind pure, **MIT**) comme banque de composants —
   markup + classes uniquement. **Stimulus conservé** (ADR-0005) pour l'interactivité ; **pas d'Alpine.js**.
4. **Abandon de Bootstrap et du thème Skote** : suppression de `bootstrap.min.css`, `components.css`
   (overrides Bootstrap) et de la dépendance à Skote (risque licence éliminé).
5. **Build intégré à la CI/au dev** : `tailwind:build --minify` **avant** `asset-map:compile` (prod) ;
   étape ajoutée à `make ci` et au hook pré-commit. `var/tailwind/` (binaire + `*.built.css`) est ignoré de git.
6. **Hooks de test et accessibilité préservés** : les classes/attributs assertés par les tests
   (`.flash-error`, `dialog.summary-dialog`, textes `<h1>`, libellés de boutons, `data-controller`…) et le
   plancher WCAG 2.2 AA restent garantis.

## Conséquences

### Positives
- **CSS purgé et léger** (poids stable quel que soit le nombre d'écrans) — fondation durable pour les lots 2→5.
- **Un seul système** : les utilitaires + `@theme` **sont** le design system ; plus de couche à réconcilier.
- **Accessibilité centralisée** : les couleurs AA sont définies une fois (plus d'override par élément).
- **Risque licence éliminé** (MIT).
- **Aucun Node.js** ajouté ; reste dans le monde PHP/AssetMapper.

### Négatives / points de vigilance
- **Introduit une étape de build** (léger, officiel, sans Node) — à industrialiser en CI/Docker (binaire
  pinné + cache ; `tailwind:build` avant `asset-map:compile`).
- **Refonte du front du Sprint 7** : layout (US-063) et reskin des écrans (US-064) sont **refaits** en
  Tailwind. Réutilisés : tokens (→ `@theme`), maquettes, contrôleurs Stimulus, **tout le backend**
  (PermissionVoter, F-S5-4), les tests, la méthodo axe-core.
- **Classes utilitaires verbeuses** dans les templates — mitigées par des composants Twig/macros et
  `@layer`/`@utility` pour les motifs récurrents (badges de statut, etc.).

## Alternatives écartées
- **Rester sur Bootstrap précompilé (ADR-0018)** : poids, deux systèmes en tension, personnalisation
  bloquée, risque licence Skote. La seule raison de ce choix (« pas de build ») ne tient plus.
- **Adopter Alpine.js (avec TailAdmin)** : redondant avec Stimulus déjà en place (ADR-0005).

## Suite
- Réinjecter `design-system.md` dans `@theme` ; refaire US-063 (layout) puis US-064 (reskin) en Tailwind ;
  re-auditer axe-core ; PR remplaçant le front de la PR #13 (backend conservé).
