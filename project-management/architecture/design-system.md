# Design System — HotOnes

**Statut :** faisant autorité (source unique de vérité pour US-061, réinjectée dans `@theme`).
**Périmètre :** tokens (couleurs, typographie, espacements, rayons, ombres, grille) et inventaire des
composants de base. Ne couvre pas le détail du reskin écran par écran (US-064) — ce document est leur
contrat visuel.
**Dépend de :** [ADR-0019](../../docs/adr/0019-frontend-tailwind-assetmapper.md) — Tailwind CSS v4
CSS-first via `symfonycasts/tailwind-bundle` (binaire autonome, **sans Node.js**), composants TailAdmin
(MIT). **Supersede** [ADR-0018](../../docs/adr/0018-integration-design-system-skote.md) (Bootstrap/Skote
précompilé), abandonné.
**Implémentation de référence :** `assets/styles/tailwind.css` — en cas d'écart entre ce document et ce
fichier, **`tailwind.css` fait foi** pour les valeurs exactes ; ce document doit être mis à jour en
conséquence.
**Réfs :** EPIC-012, US-061 (CA-1 à CA-5), US-063 (layout), US-064 (reskin), US-065 (contrastes).

---

## 1. Objet, portée, principe d'intégration

### 1.1 Principe d'intégration (ADR-0019)

Le socle reste **100 % Symfony AssetMapper**, mais la mécanique de style est désormais **Tailwind CSS v4
CSS-first**, sans Node.js :

- `symfonycasts/tailwind-bundle` télécharge et met en cache un **binaire Tailwind autonome** (version
  pinée), qui buide `assets/styles/tailwind.css` en JIT (purge) vers `var/tailwind/tailwind.built.css`,
  swappé par AssetMapper (`asset('styles/tailwind.css')`, fingerprinté).
- Le design system est déclaré **dans le CSS lui-même**, en trois temps (voir `assets/styles/tailwind.css`) :
  1. `@import "tailwindcss";` — charge le moteur et l'échelle par défaut (espacements, rayons, breakpoints,
     graisses… **non redéfinis** ici, donc **la valeur par défaut Tailwind v4 fait foi**, cf. §3).
  2. Des **tokens de thème** `--ds-*` en `:root` (clair) et `[data-theme="dark"]` (sombre) — couleurs
     contraste-vérifiées WCAG 2.2 AA (US-065).
  3. Un bloc `@theme { --color-*: var(--ds-*); … }` qui **mappe** ces tokens vers les variables de thème
     Tailwind, ce qui génère automatiquement les utilitaires `bg-primary`, `text-body`, `border-border`,
     etc. — theme-aware (basculent avec `[data-theme="dark"]`) sans classe conditionnelle dans les templates.
- **Purge JIT** via `@source "../../templates";` : seules les classes utilitaires réellement présentes dans
  les templates Twig sont buildées (~17 Ko purgé vs 305 Ko pour l'ancien `bootstrap.min.css`, cf. ADR-0019).
- **Thème sombre** piloté par l'attribut `data-theme="dark"` posé sur `<html>` (bascule Stimulus
  `theme-toggle`, persistée en `localStorage`, anti-FOUC via un script inline dans `<head>`) et lu par
  `@custom-variant dark (&:where([data-theme="dark"], [data-theme="dark"] *));` pour l'usage ponctuel du
  variant `dark:`.
- Les tokens `--ds-*` sont la **seule source de vérité** pour toute couleur ajoutée par l'application (pas
  de hex en dur dans les templates/CSS applicatifs — CA-1, CA-4). Une valeur non conforme WCAG n'est **jamais**
  introduite : les tokens ci-dessous sont déjà les valeurs corrigées (plus de distinction « brute Skote » vs
  « corrigée », cf. §5).

```css
/* assets/styles/tailwind.css — structure de référence */
@import "tailwindcss";

@source "../../templates";

@custom-variant dark (&:where([data-theme="dark"], [data-theme="dark"] *));

:root {
    --ds-primary: #4e65d4;
    /* … tokens --ds-* clairs (§2) */
}

[data-theme="dark"] {
    --ds-primary: #8092ec;
    /* … tokens --ds-* sombres (§2) */
}

@theme {
    --font-sans: "Poppins", system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
    --color-primary: var(--ds-primary);
    /* … mapping vers les variables de thème Tailwind (§2-3) */
}

@layer components {
    /* Hooks sémantiques minimaux : .visually-hidden, .flash*, .status-badge*, dialog.summary-dialog (§4) */
}
```

### 1.2 Ce que ce document fixe

1. Tokens de couleurs `--ds-*` (mappés en variables de thème Tailwind `--color-*`), clair **et** sombre.
2. Typographie, espacements, rayons, ombres, grille/breakpoints — ce qui est **surchargé** dans `@theme`
   vs ce qui reste l'**échelle par défaut Tailwind v4** (à documenter explicitement pour éviter toute
   confusion avec l'ancienne échelle Bootstrap/Skote).
3. Inventaire des recettes de composants (boutons, tables, badges, formulaires, drawer, alertes, sidebar) —
   exprimées en **classes utilitaires Tailwind**, avec les quelques classes sémantiques réellement
   définies dans `@layer components`.
4. Vérification de contraste WCAG 2.2 AA, avec corrections tracées.
5. Références TailAdmin utilisées comme banque de composants.

---

## 2. Tokens de couleurs

### 2.1 Principe

Il n'y a plus de « primitives Skote » séparées des tokens sémantiques. Chaque token `--ds-*` **est**
directement la valeur accessible retenue (US-065) — pas de valeur brute à corriger en aval. Les tokens sont
définis une fois en CSS (`:root` / `[data-theme="dark"]`) puis exposés comme variables de thème Tailwind
dans `@theme`, ce qui génère les utilitaires `bg-*`, `text-*`, `border-*` correspondants (ex. `--color-primary`
→ `bg-primary`, `text-primary`, `border-primary`, `border-primary/20`, `bg-primary/5`, etc., y compris avec
les modificateurs d'opacité Tailwind).

### 2.2 Tokens — thème clair (`:root`)

```css
:root {
    --ds-primary: #4e65d4;         /* 5.08:1 avec blanc */
    --ds-primary-fg: #ffffff;
    --ds-primary-surface: #556ee6; /* décoratif, non-texte */
    --ds-success: #2e7d32;
    --ds-warning: #60481e;
    --ds-danger: #c62828;
    --ds-info: #204260;
    --ds-on-status: #212529;       /* texte foncé sur fond de statut plein */

    --ds-bg: #f6f7f9;
    --ds-surface: #ffffff;
    --ds-surface-alt: #f8f9fa;
    --ds-ink: #212529;
    --ds-body: #495057;
    --ds-muted: #555555;
    --ds-border: #eff2f7;
    --ds-border-strong: #74788d;
    --ds-focus: #0b5fff;
}
```

### 2.3 Tokens — thème sombre (`[data-theme="dark"]`)

```css
[data-theme="dark"] {
    --ds-primary: #8092ec;
    --ds-primary-fg: #14183a;
    --ds-success: #85dbbc;
    --ds-warning: #f7d294;
    --ds-danger: #f8a6a6;
    --ds-info: #96c9f7;
    --ds-on-status: #14183a;

    --ds-bg: #222736;
    --ds-surface: #2a3042;
    --ds-surface-alt: #262b3c;
    --ds-ink: #f6f6f6;
    --ds-body: #a6b0cf;
    --ds-muted: #c3cbe4;
    --ds-border: #32394e;
    --ds-border-strong: #7b839c;
    --ds-focus: #4c8bff;
}
```

Activation : bascule utilisateur explicite (`data-theme="dark"|"light"` posé par le contrôleur Stimulus
`theme-toggle`, persisté en `localStorage`, appliqué avant peinture via un script inline anti-FOUC dans
`<head>`). Pas de fallback automatique sur `prefers-color-scheme` à ce stade.

### 2.4 Mapping `@theme` → utilitaires Tailwind

```css
@theme {
    --color-primary: var(--ds-primary);
    --color-primary-fg: var(--ds-primary-fg);
    --color-primary-surface: var(--ds-primary-surface);
    --color-success: var(--ds-success);
    --color-warning: var(--ds-warning);
    --color-danger: var(--ds-danger);
    --color-info: var(--ds-info);
    --color-on-status: var(--ds-on-status);

    --color-bg: var(--ds-bg);
    --color-surface: var(--ds-surface);
    --color-surface-alt: var(--ds-surface-alt);
    --color-ink: var(--ds-ink);
    --color-body: var(--ds-body);
    --color-muted: var(--ds-muted);
    --color-border: var(--ds-border);
    --color-border-strong: var(--ds-border-strong);
    --color-focus: var(--ds-focus);

    /* Sidebar : sombre invariante (ne suit pas la bascule de thème). */
    --color-sidebar: #2a3042;
    --color-sidebar-alt: #32394e;
    --color-sidebar-text: #a6b0cf;
    --color-sidebar-label: #9aa3bf;   /* 5.26:1 sur #2a3042 */
    --color-sidebar-icon: #6a7187;
}
```

| Variable de thème | Utilitaires générés | Usage |
|--------------------|----------------------|-------|
| `--color-primary` | `bg-primary`, `text-primary`, `border-primary`, `border-primary/20`, `bg-primary/5` | Action principale, liens, éléments actifs |
| `--color-primary-fg` | `text-primary-fg` | Texte sur fond `bg-primary` plein |
| `--color-primary-surface` | `bg-primary-surface` | Avatars, icônes conteneur, décoratif large surface |
| `--color-success` / `-warning` / `-danger` / `-info` | `text-success`, `border-warning`, … | Statuts, alertes, badges (texte/bordure) ; **fond plein au survol** des boutons d'action de statut (cf. §4.3) |
| `--color-on-status` | `text-on-status` | Texte sur fond de statut plein — boutons Approuver/Rejeter au survol (`validation.html.twig`, cf. §4.3) |
| `--color-bg` | `bg-bg` | Fond de page (`<body>`, `<main>`) |
| `--color-surface` / `-surface-alt` | `bg-surface`, `bg-surface-alt` | Cartes, tableaux, en-têtes de tableau |
| `--color-ink` | `text-ink` | Texte fort (titres, valeurs chiffrées) |
| `--color-body` | `text-body` | Texte courant |
| `--color-muted` | `text-muted` | Texte secondaire, légendes |
| `--color-border` / `-border-strong` | `border-border`, `border-border-strong` | Séparateurs décoratifs / bordures fonctionnelles (champs, boutons outline) |
| `--color-focus` | utilisé en dur dans `@layer components` (`outline: 3px solid var(--color-focus)`) | Indicateur de focus (§4.6) |
| `--color-sidebar*` | `bg-sidebar`, `text-sidebar-text`, `bg-sidebar-alt`, `text-sidebar-label` | Sidebar (toujours sombre, quel que soit le thème) |

> **Non-régression sémantique :** l'ancien couple « couleur de base / couleur `-emphasis` accessible »
> (ADR-0018) a disparu — **simplification voulue** par le passage à un système *theme-aware* : chaque token
> `--ds-{success,warning,danger,info}` **est** directement la valeur accessible retenue par US-065, et l'ancienne
> variante `-emphasis` (teinte claire pour texte sur fond sombre) correspond désormais à la **valeur du mode
> sombre** (`[data-theme="dark"]`). Un seul token par statut suffit donc, il bascule automatiquement clair/sombre.

---

## 3. Typographie, espacements, rayons, ombres, grille

### 3.1 Typographie

Police **Poppins**, chargée via Google Fonts (`base.html.twig`, `preconnect` + `<link>`), déclarée comme
police par défaut via `--font-sans` dans `@theme` — génère l'utilitaire `font-sans` (appliqué sur `<body>`).

```css
@theme {
    --font-sans: "Poppins", system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
}
```

**Échelle de tailles et graisses : non surchargée.** Tailwind v4 fournit nativement `text-xs` (12px) à
`text-3xl` (30px) et `font-normal` (400) / `font-medium` (500) / `font-semibold` (600) / `font-bold` (700) ;
ce sont ces utilitaires par défaut qui sont utilisés dans les templates (`text-xs`, `text-sm`, `text-base`,
`text-lg`, `text-2xl`, `text-3xl`, `font-medium`, `font-semibold`, `font-bold`).

**Exception documentée** : le corps de page (`<body>`) fixe une taille de référence **13px**, plus dense
que le défaut Tailwind (16px), cohérente avec l'ancien choix de densité back-office :

```html
<body class="... font-sans text-[13px] leading-normal text-body ...">
```

C'est une valeur arbitraire Tailwind (`text-[13px]`), pas un token `@theme` — à ne pas dupliquer ailleurs ;
les titres et éléments mis en avant utilisent l'échelle standard (`text-2xl`, `text-3xl`, etc.) au-dessus de
cette base. **Règle mobile conservée** : les champs de saisie tactile utilisent `text-sm`/`text-base`
(16px mini) pour éviter le zoom automatique iOS — dérogation locale au 13px de base, pas un token global.

### 3.2 Espacements

**Non surchargés.** L'échelle d'espacement Tailwind v4 par défaut (`--spacing: 0.25rem`, soit une
progression `p-1`=4px, `p-2`=8px, `p-3`=12px, `p-4`=16px, `p-5`=20px, `p-6`=24px, …) est utilisée telle
quelle par `p-*`, `m-*`, `gap-*`, `space-*` dans tous les templates (`p-5` pour les cartes KPI, `gap-3`
pour les barres d'actions, `px-4 py-3` pour les cellules de tableau, etc.). Aucun token `--spacing-*`
personnalisé n'est déclaré dans `@theme` : il n'y a **pas** de portage direct de l'ancienne échelle
Bootstrap (`$spacers` 4/8/16/24/48px) — la grille 4px de Tailwind la recouvre avec plus de granularité.

Densité compacte (tableaux de saisie) : `px-3 py-2` (au lieu de `px-4 py-3`) — convention d'usage, pas un
token dédié.

### 3.3 Rayons

**Non surchargés.** Échelle Tailwind v4 par défaut, utilisée directement dans les templates :

| Utilitaire | Valeur | Usage observé |
|------------|-------:|----------------|
| `rounded` | 0.25rem (4px) | Boutons, badges, petits éléments |
| `rounded-md` | 0.375rem (6px) | Cartes KPI, tableaux, champs de formulaire, blocs de contenu |
| `rounded-lg` | 0.5rem (8px) | Sidebar (icône logo), coins de drawer bottom-sheet (`rounded-lg rounded-lg 0 0`) |
| `rounded-full` | 9999px | Avatars, barres de progression, pastilles |

### 3.4 Ombres

**Deux valeurs surchargées** dans `@theme` (reprises des ratios validés US-065/CA-4) ; le reste de
l'échelle Tailwind (`shadow`, `shadow-lg`, `shadow-xl`, …) reste la valeur **par défaut** du framework, non
personnalisée :

```css
@theme {
    --shadow-sm: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    --shadow-md: 0 0.75rem 1.5rem rgba(18, 38, 63, 0.03);
}
```

Usage : `shadow-sm` sur les cartes (KPI, tableaux, panneaux) — cohérent avec l'esthétique TailAdmin
(ombre douce, quasi imperceptible, jamais de « box-shadow » lourd). `shadow-md` réservé aux éléments
survolés/flottants le cas échéant.

### 3.5 Grille et points de rupture

**Non surchargés.** Les breakpoints sont ceux de **Tailwind v4 par défaut** (et non plus ceux de
Bootstrap) :

| Breakpoint | Largeur mini | Usage observé dans les templates |
|------------|-------------:|-----------------------------------|
| `sm` | 640px | `sm:grid-cols-4`, `sm:col-span-1` (grille KPI) |
| `md` | 768px | `md:static md:translate-x-0` (sidebar toujours visible), `md:hidden` (overlay/bouton burger) |
| `lg` | 1024px | Réservé pour les layouts larges à venir |
| `xl` | 1280px | — |
| `2xl` | 1536px | — |

Layout applicatif (non issu de Tailwind, dimensions fixes du gabarit) : sidebar `w-[250px]`, en-tête
`h-[70px]` (classes arbitraires Tailwind, cf. `base.html.twig`) — pas de token `@theme` dédié à ce stade
(un seul usage, YAGNI).

---

## 4. Inventaire des composants

Recettes réellement utilisées dans les templates (`base.html.twig`, `valuation/index.html.twig`,
`timesheet/week.html.twig`, etc.), en **classes utilitaires Tailwind** + tokens `--color-*` du §2. Cibles
tactiles ≥ 44×44px (F-S5-1, via `min-h-11`/`h-11`/`w-11` — 11 × 4px = 44px) et parité tactile (aucune
interaction seulement au survol) s'appliquent à tous les composants interactifs, sur tous les breakpoints.
**Banque de composants visuels** : TailAdmin (MIT, `project-management/architecture/design-canvas/tailadmin-ref/`)
— cartes à ombre douce et coins arrondis, hiérarchie typographique claire, sidebar sombre, badges pilule,
tableaux aérés ; markup et classes réutilisables, adaptés aux tokens `--color-*` HotOnes (jamais les couleurs
TailAdmin brutes).

### 4.1 Bouton

**Catégorie** : Atom. Pas de classe `.btn` : chaque variante est une recette d'utilitaires Tailwind.

| Variante | Recette | Usage |
|----------|---------|-------|
| Primaire | `inline-flex min-h-11 items-center rounded bg-primary px-4 text-sm font-semibold text-white hover:opacity-90` | Action principale (« Tout enregistrer ») |
| Secondaire (outline) | `inline-flex min-h-9 items-center rounded border border-primary px-3 text-xs font-semibold text-primary hover:bg-surface-alt` | Action secondaire mise en avant (« Ma synthèse ») |
| Tertiaire (neutre) | `inline-flex min-h-9 items-center rounded border border-border-strong px-3 text-xs font-semibold text-muted hover:bg-surface-alt hover:text-ink` | Action tertiaire (« Dupliquer la semaine précédente », « Vue jour ») |
| Icône seule | `grid h-10 w-10 place-items-center rounded border border-border text-muted hover:text-ink` | Bouton burger, bascule de thème |

**États** :

| État | Traitement |
|------|-----------|
| `default` | Couleurs de la recette ci-dessus |
| `hover` | `hover:opacity-90` (plein) ou `hover:bg-surface-alt` (outline/tertiaire), transition implicite Tailwind |
| `focus-visible` | Géré globalement (§4.6), pas de classe par bouton |
| `disabled` | `disabled:opacity-65 disabled:cursor-not-allowed` (à ajouter systématiquement sur les boutons désactivables) |
| `loading` | `disabled` + spinner `currentColor`, libellé conservé (jamais l'icône seule) |

**Tokens** : `--color-primary`, `--color-border`, `--color-border-strong`, `--color-surface-alt`,
`--color-muted`, `--color-ink`.

### 4.2 Table

**Catégorie** : Organism. Pas de classe `.table` : `<table class="w-full border-collapse text-left">` +
utilitaires sur les cellules.

| Élément | Recette |
|---------|---------|
| Conteneur | `rounded-md border border-border bg-surface` (+ `overflow-x-auto` sur un wrapper pour le scroll horizontal mobile) |
| En-tête (`<thead><tr>`) | `bg-surface-alt` |
| Cellule (`<th>`/`<td>`) | `px-4 py-3` (dense : `px-3 py-2`), `text-body` (en-têtes : `font-semibold text-body`) |
| Ligne | `border-t border-border`, survol `hover:bg-surface-alt` |
| Pied (total) | `border-t-2 border-border-strong bg-surface-alt font-semibold` |

**A11y** : `<caption class="visually-hidden">` obligatoire, `<th scope="col">` / `scope="row"` pour les
en-têtes de ligne, aucun tableau utilisé pour la mise en page. Cellule cliquable → contrôle focusable réel
(lien/bouton), jamais un gestionnaire sur `<tr>` seul.

### 4.3 Badge / pastille de statut

**Catégorie** : Atom. Seul composant coloré avec une classe sémantique dédiée, définie dans
`@layer components` (motif **bordé**, texte porteur du sens, jamais la couleur seule — WCAG 1.4.1) :

```css
@layer components {
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.15rem 0.55rem;
        border: 1px solid currentColor;
        border-radius: 0.25rem;
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
    }
    .status-badge-success { color: var(--color-success); }
    .status-badge-warning { color: var(--color-warning); }
    .status-badge-danger  { color: var(--color-danger); }
    .status-badge-neutral { color: var(--color-muted); }
}
```

Usage type (`project/index.html.twig`) :

```twig
<span class="status-badge status-badge-success">{{ p.status }}</span>
```

| Statut | Classe |
|--------|--------|
| Projet — actif | `status-badge-success` |
| Projet — clôturé / neutre | `status-badge-neutral` |
| Projet — annulé | `status-badge-danger` (seule exception rouge, convention métier existante) |

**Règle absolue (WCAG 1.4.1)** : le badge porte **toujours un libellé texte** ; icône complémentaire
optionnelle mais jamais substitut au texte. Badges statiques = pas d'état interactif ; s'il devient un
filtre cliquable, il doit respecter la cible tactile 44×44px et les états du bouton (§4.1).

### 4.4 Champ de formulaire

**Catégorie** : Molecule. Pas de classe `.form-control` : recette utilitaire directe sur `<input>`/`<select>`.

Recette de référence (`timesheet/week.html.twig`) :

```
w-14 min-h-11 text-center border border-border-strong rounded-md bg-surface text-body text-sm px-1
```

| État | Traitement |
|------|-----------|
| `default` | `border border-border-strong bg-surface text-body` |
| `focus-visible` | Géré globalement (§4.6) — même contour que les boutons |
| `disabled` | `disabled:bg-surface-alt disabled:text-muted disabled:cursor-not-allowed` |
| `invalid` | bordure + texte `border-danger text-danger`, message d'erreur explicite associé par `aria-describedby` (jamais la bordure seule) |
| `valid` (optionnel) | bordure + icône `border-success text-success` |

**Mobile / tactile** : hauteur mini `min-h-11` (44px) sur les champs de saisie principaux, `inputmode`
adapté (`numeric`/`decimal`) pour les claviers mobiles, taille de police `text-sm`/`text-base` (≥16px)
pour éviter le zoom automatique iOS — dérogation assumée au texte de base 13px (§3.1).

**Tokens** : `--color-border-strong`, `--color-focus` (focus global), `--color-surface`,
`--color-surface-alt`, `--color-muted`, `--color-danger`, `--color-success`.

### 4.5 Drawer / panneau latéral (`dialog.summary-dialog`)

**Catégorie** : Organism. Élément HTML natif `<dialog>`, motif bottom-sheet → panneau latéral, stylé via
une classe sémantique définie dans `@layer components` (nom asserté par les tests, **ne pas renommer**) :

```css
@layer components {
    dialog.summary-dialog {
        width: 100%;
        max-width: 100%;
        max-height: 80vh;
        margin: auto auto 0;
        border: 1px solid var(--color-border);
        border-radius: 0.4rem 0.4rem 0 0;
        padding: 1rem;
        background: var(--color-surface);
        color: var(--color-body);
        box-sizing: border-box;
    }
    dialog.summary-dialog::backdrop { background: rgba(0, 0, 0, 0.3); }

    @media (min-width: 640px) {
        dialog.summary-dialog {
            margin: 0 0 0 auto;
            width: min(30rem, 92vw);
            max-height: 100vh;
            height: 100%;
            border-radius: 0;
        }
    }
}
```

Comportement responsive : **mobile** — ancré en bas de l'écran (bottom-sheet) ; **à partir de 640px** —
panneau latéral droit pleine hauteur (`width: min(30rem, 92vw)`). Le contenu interne (titres de section,
listes, barres de progression) utilise les utilitaires Tailwind standards (`text-sm font-semibold text-ink`,
`h-1.5 bg-border rounded-full overflow-hidden` pour les barres, cf. §4.2/§3.4).

**États** : `open`/`closed` piloté par Stimulus (`activity-summary` controller, `showModal()`/`close()`
natifs de `<dialog>`) ; fermeture via le backdrop **et** un bouton de fermeture ≥44px avec libellé
accessible (`aria-label="Fermer la synthèse"`).

### 4.6 Alerte / message flash

**Catégorie** : Molecule. Classes sémantiques asserties par les tests, définies dans `@layer components` :

```css
@layer components {
    .flash {
        border-left: 4px solid var(--color-border-strong);
        border-radius: 0.25rem;
        padding: 0.5rem 0.75rem;
        margin-bottom: 0.75rem;
    }
    .flash-success { border-left-color: var(--color-success); color: var(--color-success); }
    .flash-error   { border-left-color: var(--color-danger);  color: var(--color-danger); }
}
```

Pour les alertes contextuelles hors flash PRG (ex. bandeau « Valorisation incomplète »), la même logique
est reproduite en utilitaires directs plutôt qu'une classe dédiée (usage unique, YAGNI) :

```twig
<div class="mb-4 flex items-start gap-3 rounded border-l-4 border-warning bg-surface px-4 py-3 text-warning" role="alert">
```

| Type | Token couleur |
|------|---------------|
| Succès | `--color-success` |
| Erreur | `--color-danger` |
| Avertissement | `--color-warning` |
| Information | `--color-info` |

### 4.7 Focus visible (transversal)

**Catégorie** : règle globale, `@layer components`, s'applique à tous les éléments interactifs sans classe
à poser par composant :

```css
@layer components {
    a:focus-visible,
    button:focus-visible,
    input:focus-visible,
    select:focus-visible,
    [tabindex]:focus-visible {
        outline: 3px solid var(--color-focus);
        outline-offset: 2px;
    }
}
```

Valeurs `3px` / `2px` fixées directement dans la règle (pas de token `--focus-ring-width` séparé — un seul
point d'usage, YAGNI). `--color-focus` reste le seul token variable (clair `#0b5fff` / sombre `#4c8bff`).

### 4.8 Navigation / sidebar

**Catégorie** : Organism (US-063). Sidebar **toujours sombre**, indépendante de la bascule de thème
clair/sombre du contenu (tokens `--color-sidebar*` dédiés, §2.4).

| Élément | Recette |
|---------|---------|
| Conteneur | `fixed inset-y-0 left-0 z-30 flex w-[250px] flex-col bg-sidebar text-sidebar-text md:static md:translate-x-0` |
| En-tête (logo) | `flex h-[70px] items-center gap-3 border-b border-sidebar-alt px-5` |
| Libellé de section | `px-5 pt-4 pb-1.5 text-xs font-semibold uppercase tracking-wider text-sidebar-label` |
| Item de nav | `flex min-h-11 items-center gap-3 rounded px-3 text-[13px] hover:bg-white/5 hover:text-white` |
| Item actif | ajoute `bg-primary font-medium text-white` + `aria-current="page"` |
| Icône | `h-5 w-5 shrink-0` (SVG Heroicons outline inline, `stroke="currentColor"`) |

**Icônes** : Heroicons (MIT, outline) inline en SVG — cohérent avec la recommandation §Iconographie de la
charte UI générique, `currentColor` pour hériter de la couleur du texte parent.

---

## 5. Accessibilité (baseline)

### 5.1 Règle générale

Chaque paire texte/fond des composants ci-dessus atteint au minimum **WCAG 2.2 AA** :
- **4.5:1** pour le texte normal (< 18.66px gras / < 24px normal).
- **3:1** pour le texte large et les composants d'interface non-textuels (bordures fonctionnelles,
  indicateurs de focus — SC 1.4.11).

Les couleurs de fond servant de référence pour les vérifications ci-dessous sont les **surfaces réelles de
l'application** (`--ds-bg` = `#f6f7f9` en clair), pas un blanc pur théorique.

### 5.2 Tableau de vérification de contraste — thème clair

| Paire (tokens `--ds-*`) | Ratio | Seuil requis | Résultat |
|---|---:|---|:--:|
| `--ds-body` (#495057) sur `--ds-bg` (#f6f7f9) | **8.09:1** | 4.5:1 | ✅ |
| `--ds-muted` (#555555) sur `--ds-bg` | **6.96:1** | 4.5:1 | ✅ |
| `--ds-primary` (#4e65d4, texte/bouton) sur `--ds-bg` | **4.74:1** | 4.5:1 | ✅ |
| `--ds-primary-fg` (blanc) sur `--ds-primary` (#4e65d4) | **5.08:1** | 4.5:1 | ✅ |
| `--ds-primary-surface` (#556ee6), **usage non-texte** (icônes/avatars ≥ 3:1) sur blanc | 4.41:1 | 3:1 | ✅ (réservé au non-texte) |
| `--ds-success` (#2e7d32) sur `--ds-bg` | **4.77:1** | 4.5:1 | ✅ |
| `--ds-danger` (#c62828) sur `--ds-bg` | **5.24:1** | 4.5:1 | ✅ |
| `--ds-warning` (#60481e) sur `--ds-bg` | **8.02:1** | 4.5:1 | ✅ |
| `--ds-info` (#204260) sur `--ds-bg` | **9.75:1** | 4.5:1 | ✅ |
| `--ds-border-strong` (#74788d) sur `--ds-surface` (blanc) | **4.36:1** | 3:1 (non-texte) | ✅ |
| `--ds-focus` (#0b5fff) sur blanc | **5.13:1** | 3:1 (indicateur de focus) | ✅ |

### 5.3 Tableau de vérification de contraste — thème sombre

| Paire (tokens `--ds-*`) | Ratio | Seuil requis | Résultat |
|---|---:|---|:--:|
| `--ds-body` (#a6b0cf) sur `--ds-bg` (#222736) | **6.92:1** | 4.5:1 | ✅ |
| `--ds-primary` (#8092ec) sur `--ds-bg` | **5.12:1** | 4.5:1 | ✅ |
| `--ds-success` / `-warning` / `-danger` / `-info` (tints clairs) sur `--ds-bg` | **9.1 – 10.5:1** | 4.5:1 | ✅ |
| `--ds-border-strong` (#7b839c) sur `--ds-surface` (#2a3042, cas le plus défavorable) | **3.49:1** | 3:1 | ✅ |

### 5.4 Enseignements clés (conservés, désormais actés dans les tokens eux-mêmes)

1. **Aucun badge/alerte à fond plein coloré avec texte _blanc_** : au repos, le motif retenu (§4.3, §4.6)
   est **bordé/texte coloré sur surface neutre**. Le seul remplissage plein est **l'état survolé des boutons
   d'action de statut** (Approuver/Rejeter, `validation.html.twig`) : `hover:bg-success`/`hover:bg-danger` +
   `text-on-status` (texte foncé `#212529` clair / `#14183a` sombre — contraste AA garanti sur le fond plein),
   jamais du texte blanc sur couleur de statut.
2. **`--ds-border-strong` est réservé aux bordures fonctionnelles** (champs, boutons outline) ; `--ds-border`
   (plus clair, non garanti ≥3:1) reste purement décoratif (séparateurs, lignes de tableau).
3. Le token `--color-on-status` (`--ds-on-status`) est **utilisé** par les boutons d'action de statut au
   survol (Approuver/Rejeter, `validation.html.twig` — `hover:text-on-status` sur `hover:bg-{success,danger}`).
   Il fournit le texte à contraste AA sur le fond de statut plein et **ne doit pas être retiré** : sa
   suppression casserait le contraste du libellé au survol de ces boutons.

---

## 6. Références

- **ADR-0019** — [Intégration front : Tailwind CSS v4 via AssetMapper](../../docs/adr/0019-frontend-tailwind-assetmapper.md)
  (Tailwind v4 CSS-first, TailAdmin, sans Node.js — **fondation de ce document**).
- **ADR-0018** — [Intégration Bootstrap/Skote précompilé](../../docs/adr/0018-integration-design-system-skote.md)
  (**superseded** par ADR-0019, conservé pour historique uniquement).
- **Implémentation** : `assets/styles/tailwind.css` — source de vérité pour les valeurs exactes de tokens.
- **TailAdmin (MIT)** : banque de composants visuels de référence —
  `project-management/architecture/design-canvas/tailadmin-ref/` (captures d'écran du thème).
- **Templates de référence** : `templates/base.html.twig` (layout, sidebar, topbar), `templates/valuation/index.html.twig`
  (cartes KPI, tableau, détails), `templates/timesheet/week.html.twig` (formulaire de saisie, drawer,
  barres de progression).
- **WCAG 2.2** : niveau AA — [w3.org/TR/WCAG22](https://www.w3.org/TR/WCAG22/), critères 1.4.1 (usage de
  la couleur), 1.4.3 (contraste minimum), 1.4.11 (contraste du contenu non textuel), 2.4.7 (focus visible),
  2.4.11 (focus non masqué), 2.5.5/2.5.8 (taille des cibles).
- **EPIC-012 / US-061** : `project-management/backlog/epics/EPIC-012-integration-design.md`,
  `project-management/backlog/user-stories/US-061-charte-design-system.md`.

---

**Date de dernière mise à jour :** 2026-09-03
**Auteur :** UI Designer (agent), pour EPIC-012 / US-061 — resynchronisation post-migration Tailwind (ADR-0019)
