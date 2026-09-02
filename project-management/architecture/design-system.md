# Design System — HotOnes

**Statut :** faisant autorité (source unique de vérité pour US-061).
**Périmètre :** tokens (couleurs, typographie, espacements, rayons, ombres, grille) et inventaire des
composants de base. Ne couvre pas le reskin des écrans (US-064) ni le layout Skote (US-063) — ce document
est leur contrat visuel.
**Dépend de :** ADR-0018 (intégration CSS Bootstrap/Skote compilé + custom properties, sans build Sass).
**Réfs :** EPIC-012, US-061 (CA-1 à CA-5).

---

## 1. Objet, portée, principe d'intégration

### 1.1 Principe d'intégration (ADR-0018)

Le socle reste **100 % Symfony AssetMapper**, sans étape de compilation Sass/npm :

- Le CSS **compilé** de Skote/Bootstrap (`Skote_Symfony_v2.2.0/.../dist` ou équivalent importmap) est
  chargé tel quel — ses classes utilitaires (`.btn`, `.badge`, `.table`, `.card`, `.form-control`, `.offcanvas`,
  breakpoints `sm/md/lg/xl/xxl`…) sont **figées** : on ne peut pas les recompiler avec des `$variables`
  Sass modifiées.
- Une **couche de tokens** en CSS custom properties (`--variables`), définie dans `assets/styles/app.css`
  (ou un fichier dédié `assets/styles/tokens.css` importé avant le reste), **dérive** ses valeurs des
  variables Skote (couleurs, typo, espacements, rayons, ombres) mais les exprime nativement en CSS.
- Les tokens sont la **seule source de vérité** pour toute valeur de style ajoutée par l'application
  (pas de hex en dur dans les templates/CSS applicatifs — CA-1, CA-4).
- Là où un token corrige une valeur Skote non conforme WCAG (§5), c'est le **token corrigé** qui fait foi,
  pas la variable Sass d'origine (le Sass n'est de toute façon plus recompilé).

### 1.2 Ce que ce document fixe

1. Tokens de couleurs (primitives + sémantiques), clair **et** sombre.
2. Typographie, espacements, rayons, ombres, grille/breakpoints.
3. Inventaire des composants de base (boutons, tables, badges, formulaires, drawer, alertes, sidebar).
4. Vérification de contraste WCAG 2.2 AA, avec corrections tracées.
5. Table de migration depuis `assets/styles/app.css`.

---

## 2. Tokens de couleurs

### 2.1 Primitives (dérivées de `_variables.scss` Skote)

Les primitives ne sont **jamais utilisées directement** dans les composants ou les templates — elles
n'existent que pour dériver les tokens sémantiques (§2.2). C'est la règle CA-1 : *« chaque token est nommé
de façon sémantique, pas `--red-500` en usage »*.

```css
:root {
  /* Palette Skote — primitives, usage interne uniquement */
  --palette-blue: #556ee6;
  --palette-green: #34c38f;
  --palette-yellow: #f1b44c;
  --palette-red: #f46a6a;
  --palette-cyan: #50a5f1;
  --palette-orange: #f1734f;
  --palette-indigo: #564ab1;

  --palette-gray-100: #f8f9fa;
  --palette-gray-200: #eff2f7;
  --palette-gray-300: #f6f6f6;
  --palette-gray-400: #ced4da;
  --palette-gray-500: #adb5bd;
  --palette-gray-600: #74788d;
  --palette-gray-700: #495057;
  --palette-gray-800: #343a40;
  --palette-gray-900: #212529;
  --palette-white: #ffffff;
  --palette-black: #000000;
}
```

### 2.2 Tokens sémantiques — thème clair (défaut)

> **Correction importante (CA-5) :** plusieurs couleurs Skote telles quelles échouent au contrôle de
> contraste WCAG 2.2 AA dans leurs usages courants (texte, bordure fonctionnelle, texte blanc sur fond
> plein). Les valeurs ci-dessous sont **les valeurs corrigées** — voir le détail et les ratios au §5.

```css
:root,
[data-theme="light"] {
  color-scheme: light;

  /* Marque / actions primaires — accessible : #556ee6 assombri de ~8 % (4.41:1 → 5.08:1) */
  --color-primary: #4e65d4;
  /* Teinte Skote d'origine — réservée aux grandes surfaces décoratives (sidebar active, focus-ring,
     accents de graphique) : jamais comme couleur de texte ou de libellé de petit bouton. */
  --color-primary-surface: #556ee6;
  --color-on-primary: #ffffff; /* texte sur --color-primary : 5.08:1 */

  /* Sémantiques de statut — base (fonds de badge/bouton, icônes) + emphasis (texte accessible) */
  --color-success: var(--palette-green);
  --color-success-emphasis: #2e7d32; /* repris de app.css, 4.77:1 sur fond page, 5.11:1 sur blanc */
  --color-warning: var(--palette-yellow);
  --color-warning-emphasis: #60481e; /* shade 60% de #f1b44c, 8.02:1 sur fond page */
  --color-danger: var(--palette-red);
  --color-danger-emphasis: #c62828; /* repris de app.css, 5.24:1 sur fond page */
  --color-info: var(--palette-cyan);
  --color-info-emphasis: #204260; /* shade 60% de #50a5f1, 9.75:1 sur fond page */

  /* Texte sur fond plein (boutons solides success/warning/danger/info) : texte FONCÉ, pas blanc — voir
     §5.2, le blanc échoue le contraste sur ces 4 couleurs à pleine saturation. Primary fait exception
     (texte blanc, cf. --on-primary). */
  --color-on-status: var(--palette-gray-900); /* #212529, 5.2:1 à 8.4:1 selon la couleur de fond */

  /* Alias explicites pour les statuts métier (complétude, statut projet) — mêmes valeurs que les
     tokens sémantiques ci-dessus, nommage dédié pour la lisibilité dans les composants métier. */
  --color-status-success: var(--color-success-emphasis);
  --color-status-warning: var(--color-warning-emphasis);
  --color-status-danger: var(--color-danger-emphasis);
  --color-status-neutral: var(--palette-gray-600);   /* usage non-texte / état désactivé, cf. §5.2 */
  --color-status-neutral-emphasis: #555555;          /* variante texte, accessible (6.96:1) */

  /* Surfaces */
  --color-bg: #f6f7f9;         /* fond de page — repris tel quel de app.css (PR #11) */
  --color-surface: #ffffff;    /* cartes, panneaux, drawer */
  --color-surface-alt: var(--palette-gray-100); /* en-têtes de tableau, zones alternées */

  /* Bordures — deux niveaux, cf. §5.2 (le gris Skote par défaut échoue le contraste fonctionnel) */
  --color-border-subtle: var(--palette-gray-200); /* décoratif uniquement (séparateurs, lignes de table) */
  --color-border-strong: var(--palette-gray-600); /* fonctionnel : champs, boutons outline, sélecteurs (4.36:1) */

  /* Texte */
  --color-text: var(--palette-gray-700);       /* texte principal, 8.09:1 */
  --color-text-muted: #555555;                 /* texte secondaire, repris de app.css, 6.96:1 */
  --color-text-disabled: var(--palette-gray-600); /* exempté WCAG (état désactivé uniquement) */
  --color-ink: var(--palette-gray-900);

  /* Liens */
  --color-link: var(--color-primary);
  --color-link-hover: #3a4fb8;

  /* Focus (WCAG 2.4.7 / 2.4.11) — conservé tel quel depuis app.css, volontairement distinct du bleu de
     marque pour rester très saillant : 5.13:1 sur blanc, > 3:1 requis pour un indicateur de focus. */
  --color-focus: #0b5fff;
  --focus-ring-width: 3px;
  --focus-ring-offset: 2px;
}
```

### 2.3 Tokens sémantiques — thème sombre

Même structure de noms, valeurs distinctes (CA-2). Activation par `[data-theme="dark"]` (bascule
utilisateur explicite, prioritaire) ou `prefers-color-scheme: dark` en l'absence de préférence explicite.

```css
[data-theme="dark"] {
  color-scheme: dark;

  --color-primary: #8092ec;         /* tint 25% de #556ee6, 5.12:1 sur fond page sombre */
  --color-primary-surface: #556ee6;
  --color-on-primary: #14183a;      /* texte foncé sur le bleu clair en mode sombre */

  --color-success: var(--palette-green);
  --color-success-emphasis: #85dbbc; /* tint 40% Skote (pattern *-text-emphasis-dark), 9.12:1 */
  --color-warning: var(--palette-yellow);
  --color-warning-emphasis: #f7d294; /* tint 40% Skote */
  --color-danger: var(--palette-red);
  --color-danger-emphasis: #f8a6a6;  /* tint 40% Skote */
  --color-info: var(--palette-cyan);
  --color-info-emphasis: #96c9f7;    /* tint 40% Skote */

  --color-on-status: #14183a; /* texte foncé sur les fonds de statut clairs ci-dessus */

  --color-status-success: var(--color-success-emphasis);
  --color-status-warning: var(--color-warning-emphasis);
  --color-status-danger: var(--color-danger-emphasis);
  --color-status-neutral: #a6b0cf;
  --color-status-neutral-emphasis: #c3cbe4;

  --color-bg: #222736;          /* body-bg-dark Skote */
  --color-surface: #2a3042;     /* body-secondary-bg-dark Skote (= sidebar-dark-bg) */
  --color-surface-alt: #262b3c;

  --color-border-subtle: #32394e;   /* border-color-dark Skote */
  --color-border-strong: #7b839c;   /* corrigé — cf. §5.2, 3.49:1 mini sur surface sombre */

  --color-text: #a6b0cf;        /* body-color-dark Skote, 6.92:1 */
  --color-text-muted: #c3cbe4;  /* body-secondary-color-dark Skote, contraste supérieur au texte principal */
  --color-text-disabled: #6a7187;
  --color-ink: #f6f6f6;

  --color-link: var(--color-primary);
  --color-link-hover: #a3b1f3;

  --color-focus: #4c8bff; /* variante plus claire du focus pour rester visible sur fond sombre */
}
```

---

## 3. Typographie, espacements, rayons, ombres, grille

### 3.1 Typographie

Police Skote inchangée (`Poppins`), échelle et graisses dérivées de `_variables.scss`.

```css
:root {
  --font-family-base: "Poppins", system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
  --font-family-mono: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;

  --font-size-base: 0.8125rem; /* 13px — taille de référence Skote */
  --font-size-sm: 0.7109rem;   /* ~11.4px */
  --font-size-lg: 1.0156rem;   /* ~16.25px */

  --font-size-h6: var(--font-size-base); /* 13px */
  --font-size-h5: 1.0156rem;             /* 16.25px */
  --font-size-h4: 1.2188rem;             /* 19.5px */
  --font-size-h3: 1.4219rem;             /* 22.75px */
  --font-size-h2: 1.625rem;              /* 26px */
  --font-size-h1: 2.03125rem;            /* 32.5px */

  --font-weight-light: 300;
  --font-weight-normal: 400;
  --font-weight-medium: 500;
  --font-weight-semibold: 600; /* = "bold" Skote ($font-weight-bold: 600) */

  --line-height-base: 1.5;
  --line-height-sm: 1.25;
  --line-height-lg: 2;
}
```

> Note : `--font-size-base` (13px) est plus petit que le défaut Bootstrap (16px) — c'est un choix Skote
> assumé pour un back-office dense. Les tailles de police des champs de saisie tactile (US-052, mobile)
> restent à **16px minimum** pour éviter le zoom automatique iOS (déjà respecté dans `app.css`, cf. §6) ;
> ce n'est **pas** un token typographique global mais une règle spécifique aux `<input>` sur mobile.

### 3.2 Espacements

Échelle Bootstrap `$spacers` (utilisée telle quelle par les classes utilitaires `.p-*`, `.m-*`, `.gap-*`
du CSS compilé — ne pas en inventer une autre, elle ne matcherait pas les classes déjà livrées).

```css
:root {
  --space-0: 0;
  --space-1: 0.25rem;  /* 4px */
  --space-2: 0.5rem;   /* 8px */
  --space-3: 1rem;     /* 16px */
  --space-4: 1.5rem;   /* 24px */
  --space-5: 3rem;     /* 48px */

  --grid-gutter: 24px;
}
```

Pour les tableaux denses (saisie de temps, listes), une densité **compacte** est admise en alternative à
la densité par défaut Skote (§4.2) : `--space-2` (8px vertical / horizontal) au lieu de la densité
`--table-cell-padding-*` par défaut.

### 3.3 Rayons

```css
:root {
  --radius-sm: 0.2rem;    /* 3.2px */
  --radius-md: 0.25rem;   /* 4px — défaut boutons, champs, cartes, badges */
  --radius-lg: 0.4rem;    /* 6.4px — modales, drawer */
  --radius-xl: 1rem;
  --radius-2xl: 2rem;
  --radius-pill: 50rem;   /* badges pilule, boutons ronds */
}
```

### 3.4 Ombres

```css
:root {
  --shadow-sm: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
  --shadow-md: 0 0.75rem 1.5rem rgba(18, 38, 63, 0.03);
  --shadow-lg: 0 1rem 3rem rgba(0, 0, 0, 0.175);
  --shadow-inset: inset 0 1px 2px rgba(0, 0, 0, 0.075);
}
```

### 3.5 Grille et points de rupture

Le CSS Bootstrap étant **compilé** (non recompilable sans build Sass), les points de rupture réels des
classes utilitaires (`.d-md-none`, conteneurs, colonnes) sont ceux de Bootstrap par défaut :

```css
:root {
  --bp-sm: 576px;
  --bp-md: 768px;
  --bp-lg: 992px;
  --bp-xl: 1200px;
  --bp-xxl: 1400px;
}
```

- Grille : 12 colonnes, gouttière `--grid-gutter` (24px).
- Layout : `--sidebar-width: 250px` (`--sidebar-width-collapsed: 70px`), `--header-height: 70px`,
  `--footer-height: 60px` (repris de Skote, utilisés par US-063).

**Réconciliation avec le point de rupture 640px existant** (déjà utilisé en CSS applicatif brut, hors
classes Bootstrap, dans `.day-submit-bar`, `.summary-dialog`) : ces media queries `@media (min-width: 640px)`
ne dépendent pas des classes Bootstrap compilées — elles continuent de fonctionner sans modification. Pour
les **nouveaux** composants custom (non issus des classes Bootstrap), privilégier `--bp-md` (768px) comme
pivot mobile → desktop par cohérence avec le reste du thème ; les 640px déjà en place sont une exception
tolérée et documentée (§6), pas un précédent à reproduire.

---

## 4. Inventaire des composants

Pour chaque composant : anatomie, états, tokens utilisés, exigences a11y. Cibles tactiles ≥ 44×44px
(F-S5-1) et parité tactile (aucune interaction seulement au survol) s'appliquent à tous les composants
interactifs, sur tous les breakpoints — pas uniquement mobile.

### 4.1 Bouton (`.btn`)

**Catégorie** : Atom.

| Variante | Fond | Texte | Usage |
|----------|------|-------|-------|
| `primary` | `--color-primary` | `--color-on-primary` (blanc) | Action principale |
| `success` / `warning` / `danger` / `info` | `--color-{name}` | `--color-on-status` (foncé) | Actions sémantiques (valider, alerter, supprimer, information) |
| `outline-*` | transparent | `--color-{name}-emphasis` + bordure `--color-{name}-emphasis` | Action secondaire |
| `ghost` / `link` | transparent | `--color-link` | Action tertiaire |

**Anatomie** : padding `0.47rem 0.75rem` (sm : `0.25rem 0.5rem` / lg : `0.5rem 1rem`), `--radius-md`,
`font-weight: var(--font-weight-normal)`, hauteur mini tactile 44px (padding ajusté sur mobile si besoin,
la hauteur calculée par défaut Skote ~36–38px est **augmentée** en dessous de `--bp-md`).

**États** :

| État | Traitement |
|------|-----------|
| `default` | Couleurs de variante ci-dessus |
| `hover` | Fond assombri/éclairci ~15 % (`filter` ou couleur pré-calculée), `transition: 150ms ease-out` |
| `focus-visible` | **Contour** `var(--focus-ring-width) solid var(--color-focus)`, `outline-offset: var(--focus-ring-offset)` — remplace le box-shadow de focus par défaut de Skote (désactivé côté Skote, `$input-focus-box-shadow: none`), donc **aucun conflit** : l'app fournit le seul traitement de focus. |
| `active` | Fond assombri ~20 %, `box-shadow: var(--shadow-inset)` |
| `disabled` | `opacity: 0.65`, `cursor: not-allowed`, pas de `hover`/`focus` |
| `loading` | `disabled` + spinner (`currentColor`), libellé conservé (jamais l'icône seule) |

**Tokens** : `--color-primary`, `--color-{success,warning,danger,info}`, `--color-{...}-emphasis`,
`--color-on-primary`, `--color-on-status`, `--radius-md`, `--space-2`, `--space-3`, `--color-focus`.

### 4.2 Table (`.table`)

**Catégorie** : Organism.

| Élément | Token |
|---------|-------|
| Fond d'en-tête | `--color-surface-alt` |
| Bordure de ligne | `--color-border-subtle` |
| Fond au survol de ligne | `--color-surface-alt` |
| Texte | `--color-text` |

**Densités** :
- **Défaut Skote** : `padding: 0.75rem` (`--space-3`).
- **Compacte** (données de saisie de temps, listes denses — déjà en usage dans `.project-list`,
  `.reminders-history`) : `padding: var(--space-2) calc(var(--space-2) * 1.2)` (~8px/10px), en alternative
  explicitement admise par densité, pas une dérive.

**A11y** : `<caption>` ou titre associé (peut être `.visually-hidden`), en-têtes `<th scope="col">`, pas
de tableau utilisé pour la mise en page. Les lignes cliquables (navigation vers détail) doivent exposer
un contrôle focusable (lien englobant le libellé principal), pas un `onclick` sur `<tr>` seul.

### 4.3 Badge / pastille de statut

**Catégorie** : Atom. Motif retenu : **bordé**, fond transparent/surface — pas de remplissage plein
(voir §5.2 : le remplissage plein en teinte pastel est trop proche du seuil de contraste pour être une
valeur par défaut sûre sur toutes les surfaces). Ce motif formalise le composant `.project-status-badge`
déjà en place dans `app.css`.

**Anatomie** : `padding: 0.15rem 0.55rem`, `border: 1px solid`, `--radius-md` (ou `--radius-pill` en
variante « pilule »), `font-weight: var(--font-weight-semibold)`, `font-size: var(--font-size-sm)`.

**Règle absolue (WCAG 1.4.1)** : le badge porte **toujours un libellé texte** ; la couleur renforce, ne
porte jamais seule le sens. Icône complémentaire optionnelle mais jamais substitut au texte.

| Statut | Token bordure/texte |
|--------|----------------------|
| Complétude — soumis | `--color-status-success` |
| Complétude — partiel | `--color-status-warning` |
| Complétude — vide / J+2 dépassé | `--color-status-danger` |
| Complétude — en cours | `--color-status-neutral-emphasis` |
| Projet — actif | `--color-status-success` |
| Projet — neutre | `--color-status-neutral-emphasis` |
| Projet — clôturé | `--color-status-neutral-emphasis` + fond `--color-surface-alt` |
| Projet — annulé | `--color-status-danger` (seule exception rouge, cf. convention projet existante) |

**États** : badges statiques = pas d'état interactif. Si le badge devient un **filtre cliquable** (futur),
il doit respecter la cible tactile 44×44px et les états `hover`/`focus-visible`/`active` du bouton (§4.1).

### 4.4 Champ de formulaire (`.form-control`, `.form-select`)

**Catégorie** : Molecule.

**Anatomie** : padding `0.47rem 0.75rem`, `--radius-md`, bordure `1px solid var(--color-border-strong)`
(**corrigé**, cf. §5.2 — la bordure Skote par défaut, gris `#ced4da`, est sous le seuil de contraste
fonctionnel 3:1), fond `--color-surface`.

**États** :

| État | Traitement |
|------|-----------|
| `default` | bordure `--color-border-strong` |
| `hover` | bordure légèrement assombrie (décoratif, non porteur de sens) |
| `focus-visible` | contour `var(--focus-ring-width) solid var(--color-focus)` (identique au bouton — Skote désactive son propre `box-shadow` de focus sur les champs, l'app est la seule source du traitement de focus) |
| `disabled` | fond `--color-surface-alt`, texte `--color-text-disabled`, `cursor: not-allowed` |
| `invalid` | bordure + texte d'erreur `--color-danger-emphasis`, message d'erreur explicite associé par `aria-describedby` (jamais la bordure rouge seule) |
| `valid` (optionnel) | bordure + icône `--color-success-emphasis` |

**Mobile / tactile** : hauteur mini **44px** sur les écrans de saisie principaux (déjà en place pour
`.day-entry-duration`/`.day-entry-comment`/`.project-form-field input`), `font-size: 1rem` (16px) minimum
sur mobile pour éviter le zoom automatique iOS — dérogation assumée à `--font-size-base` (13px), documentée
comme règle spécifique aux champs de saisie tactile, pas comme un token global.

**Tokens** : `--color-border-strong`, `--color-focus`, `--color-surface`, `--color-surface-alt`,
`--color-text-disabled`, `--color-danger-emphasis`, `--radius-md`.

### 4.5 Drawer / panneau latéral (`.offcanvas` + motif bottom-sheet)

**Catégorie** : Organism.

Deux comportements responsive coexistent, tous deux tokenisés :

1. **Offcanvas Skote standard** (panneaux de configuration, filtres) : largeur `400px` (horizontal),
   padding `1rem`, fond `--color-surface`, bordure `--color-border-subtle`, ombre `--shadow-sm`,
   fond de recouvrement `rgba(0, 0, 0, 0.5)`, transition `300ms`.
2. **Bottom-sheet → panneau droit** (motif `.summary-dialog` existant, ex. « Ma synthèse ») : sur mobile,
   ancré en bas (`border-radius: var(--radius-lg) var(--radius-lg) 0 0`) ; à partir de `--bp-md`,
   panneau latéral droit pleine hauteur (`width: min(30rem, 92vw)`). Ce motif est **conservé tel quel**
   (plus adapté à une UX mobile-first que l'offcanvas générique, qui n'a pas de mode bottom-sheet) mais
   **restylé avec les tokens** (`--color-surface`, `--color-border-subtle`, `--shadow-lg`, `--radius-lg`)
   au lieu des hex en dur (`#ccc`, `#fff`) actuels.

**États** : `open`/`closed` (transition `transform`/`opacity`, jamais de propriété qui déclenche un
reflow coûteux), fermeture au clic sur le fond de recouvrement **et** via un bouton de fermeture ≥44px
avec libellé accessible (`aria-label="Fermer"` a minima, texte visuellement masqué recommandé).

### 4.6 Alerte / message flash

**Catégorie** : Molecule. Même motif que le badge : fond teinté léger optionnel, texte et bordure en
`-emphasis` (garantit le contraste, cf. §5.2), jamais fond plein + texte blanc.

| Type | Tokens |
|------|--------|
| Succès | bordure/texte `--color-success-emphasis` |
| Erreur | bordure/texte `--color-danger-emphasis` |
| Avertissement | bordure/texte `--color-warning-emphasis` |
| Information | bordure/texte `--color-info-emphasis` |

**Anatomie** : `padding: 0.75rem 1.25rem`, `border-left: 4px solid`, `--radius-md`, fond `--color-surface`
ou une nuance très légère de la couleur (à vérifier au cas par cas si un fond teinté est introduit, cf.
avertissement §5.2 sur la marge de contraste serrée des fonds teintés).

### 4.7 Navigation / sidebar (Skote)

**Catégorie** : Organism (US-063, référencé ici pour les tokens).

| Élément | Clair | Sombre |
|---------|-------|--------|
| Fond sidebar | `--color-surface` (blanc) | `--color-surface` (`#2a3042`) |
| Item actif | `--color-primary-surface` (fond) + texte blanc (**large/gras**, contexte non-texte-fin, cf. §5.2) | idem |
| Item icône | `--palette-gray-600` | `#6a7187` |
| Item hover | `--color-ink` | `#ffffff` |
| Largeur | `--sidebar-width` (250px), réduit `--sidebar-width-collapsed` (70px) | idem |

---

## 5. Accessibilité (baseline)

### 5.1 Règle générale

Chaque paire texte/fond des composants ci-dessus atteint au minimum **WCAG 2.2 AA** :
- **4.5:1** pour le texte normal (< 18.66px gras / < 24px normal).
- **3:1** pour le texte large et les composants d'interface non-textuels (bordures fonctionnelles,
  indicateurs de focus — SC 1.4.11).

Les couleurs de fond servant de référence pour les vérifications ci-dessous sont les **surfaces réelles
de l'application** (`--color-bg` = `#f6f7f9`, pas un blanc pur théorique), pour un contrôle réaliste.

### 5.2 Tableau de vérification de contraste (thème clair)

| Paire | Ratio | Seuil requis | Résultat | Correction appliquée |
|-------|------:|--------------|:--:|-----------------------|
| `--color-text` (#495057) sur `--color-bg` (#f6f7f9) | **8.09:1** | 4.5:1 | ✅ | — |
| `--color-text-muted` (#555555) sur `--color-bg` | **6.96:1** | 4.5:1 | ✅ | — (repris de `app.css`) |
| `--color-primary` (#4e65d4, texte/bouton) sur `--color-bg` | **4.74:1** | 4.5:1 | ✅ | **Corrigé** — Skote `#556ee6` d'origine ne donnait que **4.41:1** (échec) ; assombri de ~8 % |
| `--color-on-primary` (blanc) sur `--color-primary` (#4e65d4) | **5.08:1** | 4.5:1 | ✅ | idem |
| `--color-primary-surface` (#556ee6, teinte Skote brute) sur blanc, **usage non-texte** (icônes/surfaces ≥ 3:1) | 4.41:1 | 3:1 | ✅ | Conservée telle quelle, réservée aux usages non-texte |
| `--color-success-emphasis` (#2e7d32) sur `--color-bg` | **4.77:1** | 4.5:1 | ✅ | — (repris de `app.css`) |
| `--color-danger-emphasis` (#c62828) sur `--color-bg` | **5.24:1** | 4.5:1 | ✅ | — (repris de `app.css`) |
| `--color-warning-emphasis` (#60481e) sur `--color-bg` | **8.02:1** | 4.5:1 | ✅ | **Introduit** — le jaune Skote brut (#f1b44c) en texte donne 1.4:1 (échec massif) |
| `--color-info-emphasis` (#204260) sur `--color-bg` | **9.75:1** | 4.5:1 | ✅ | **Introduit** — le cyan Skote brut donne 2.6:1 en texte blanc / 2.9:1 en texte direct sur blanc (échec) |
| `--color-on-status` (#212529) sur `--color-success` (#34c38f) | **6.87:1** | 4.5:1 | ✅ | **Corrigé** — texte blanc sur ce fond ne fait que **2.25:1** (échec massif) |
| `--color-on-status` sur `--color-warning` (#f1b44c) | **8.35:1** | 4.5:1 | ✅ | idem, texte blanc = **1.85:1** |
| `--color-on-status` sur `--color-danger` (#f46a6a) | **5.23:1** | 4.5:1 | ✅ | idem, texte blanc = **2.95:1** |
| `--color-on-status` sur `--color-info` (#50a5f1) | **5.87:1** | 4.5:1 | ✅ | idem, texte blanc = **2.63:1** |
| `--color-border-strong` (#74788d) sur `--color-surface` (blanc) | **4.36:1** | 3:1 (non-texte) | ✅ | **Corrigé** — bordure de champ Skote par défaut (#ced4da) ne fait que **1.49:1** (échec, invisible fonctionnellement) |
| `--color-focus` (#0b5fff) sur blanc | **5.13:1** | 3:1 (indicateur de focus) | ✅ | Conservé (déjà conforme dans `app.css`) |
| Badge annulé : `--color-danger-emphasis` sur `--color-surface-alt` | ≥ 5.1:1 (surface plus claire que `--color-bg`) | 4.5:1 | ✅ | — |

### 5.3 Tableau de vérification de contraste (thème sombre)

| Paire | Ratio | Seuil requis | Résultat | Correction appliquée |
|-------|------:|--------------|:--:|-----------------------|
| `--color-text` (#a6b0cf) sur `--color-bg` (#222736) | **6.92:1** | 4.5:1 | ✅ | — (défaut Skote, déjà conforme) |
| `--color-primary` (#8092ec) sur `--color-bg` | **5.12:1** | 4.5:1 | ✅ | **Introduit** — le bleu de marque non retinté ne fait que **3.37:1** en texte sur fond sombre (échec) |
| `--color-success/warning/danger/info-emphasis` (tint 40 % Skote) sur `--color-bg` | **9.1 – 10.5:1** (vérifié sur success, tendance confirmée sur les 3 autres) | 4.5:1 | ✅ | Motif `*-text-emphasis-dark` déjà présent dans les variables Skote, réutilisé tel quel |
| `--color-border-strong` (#7b839c) sur `--color-surface` (#2a3042, cas le plus défavorable) | **3.49:1** | 3:1 | ✅ | **Introduit** — la bordure translucide Skote (#353d55) ne fait que **1.22:1** sur cette même surface (échec) |

### 5.4 Enseignements clés

1. **Aucun bouton/badge à fond plein success/warning/danger/info avec texte blanc n'est accessible** avec
   les couleurs Skote à pleine saturation. Règle retenue : texte **foncé** (`--color-on-status`) sur ces
   quatre fonds ; le bleu de marque reste la seule couleur utilisée avec du texte blanc (après léger
   assombrissement).
2. **Les bordures de champs de formulaire Skote par défaut sont fonctionnellement invisibles** (1.49:1,
   très en dessous du seuil de 3:1 pour un composant d'interface). Un token de bordure plus soutenu
   (`--color-border-strong`) est introduit spécifiquement pour cet usage, distinct des séparateurs
   purement décoratifs qui restent avec la teinte claire Skote.
3. Le motif « badge/alerte bordé, texte `-emphasis` sur surface claire » retenu au §4 est celui qui offre
   la **plus grande marge** de contraste (5:1 à 10:1 selon la couleur) — préféré au remplissage pastel
   plein, dont la marge s'est révélée trop juste (~4.57:1 mesuré sur un exemple de fond teinté vert clair)
   pour être une valeur par défaut sûre sur toutes les surfaces de l'application.

---

## 6. Migration depuis `assets/styles/app.css`

| Valeur actuelle (`app.css`) | Usage | Token cible | Remarque |
|------------------------------|-------|-------------|----------|
| `background-color: #f6f7f9` (body) | Fond de page | `--color-bg` | Valeur identique, aucune régression |
| `color: #1f2328` (body) | Texte principal | `--color-text` (#495057) | Léger changement de teinte (plus proche de Skote), contraste conservé ≥ AA |
| `color: #555` (`.hint`, `.reminder-banner`, `.summary-planning`) | Texte secondaire | `--color-text-muted` | Valeur reprise à l'identique |
| `border-left-color: #2e7d32` (`.flash-success`) | Succès | `--color-success-emphasis` | Valeur reprise à l'identique |
| `border-left-color: #c62828` (`.flash-error`) | Erreur | `--color-danger-emphasis` | Valeur reprise à l'identique |
| `outline: 3px solid #0b5fff` (`:focus-visible`) | Focus | `--color-focus` + `--focus-ring-width` | Valeur reprise à l'identique |
| `.project-status-active { border-color: #2e7d32; color: #2e7d32 }` | Statut projet actif | `--color-status-success` | Alias direct |
| `.project-status-cancelled { border-color: #c62828; color: #c62828 }` | Statut projet annulé | `--color-status-danger` | Alias direct, conserve « seule exception rouge » |
| `.project-status-neutral / .project-status-closed { color: #555 / #777 }` | Statuts neutre/clôturé | `--color-status-neutral-emphasis` | Légère harmonisation (#777 → #555, gain de contraste) |
| `.badge-escalated { border: 1px solid #777 }` | Badge escaladé | `--color-status-neutral-emphasis` | idem |
| `border-left-color: #b26a00` / `background: #fff8e6` (`.offline-banner`, `.project-budget-gap`) | Avertissement (décoratif, non porteur seul du sens) | bordure → `--color-warning-emphasis` ; fond → `--color-surface-alt` ou fond teinté à vérifier au cas par cas | **Attention** : `#b26a00` en tant que texte échouerait AA (4.24:1) — n'est utilisé ici qu'en bordure/fond, conforme ; ne pas le réutiliser comme couleur de texte |
| `border: 1px solid #ddd` / `#eee` (cartes, tableaux, tabs) | Séparateurs décoratifs | `--color-border-subtle` | Aucune exigence de contraste (décoratif) |
| `border: 1px solid #999` (day-nav-btn, badges) | Bordures fonctionnelles | `--color-border-strong` | Gain de contraste (#999 ≈ 2.5:1 → #74788d 4.36:1) |
| `#0b5fff` (`.summary-bar-fill`) | Barre de progression | `--color-primary` (ou `--color-primary-surface` si contexte large/non-texte) | Le libellé texte adjacent porte déjà le sens (WCAG 1.4.1), pas de régression |
| Rayons ad hoc (`4px`, `6px`, `12px`) | Cartes, boutons, drawer | `--radius-md` / `--radius-lg` / `--radius-lg` (bottom-sheet) | Valeurs déjà quasi identiques |
| `min-height: 44px` / `min-width: 44px` (multiples endroits) | Cibles tactiles | Conservé tel quel (déjà conforme F-S5-1), pas de token dédié nécessaire au-delà d'une convention documentée ici | — |

**Aucune régression de fond global** : `--color-bg` reprend exactement `#f6f7f9` posé par la PR #11 ; le
placeholder `skyblue` reste éliminé. Les seuls changements de teinte (texte principal, statuts neutre/clôturé,
bordures fonctionnelles) sont des **gains** de contraste, jamais des pertes.

---

## 7. Références

- **Thème Skote** : `project-management/Skote_Symfony_v2.2.0/Starterkit/assets/scss/_variables.scss`,
  `_variables-dark.scss` (et équivalents `Admin/`).
- **Base UI actuelle** : `assets/styles/app.css`.
- **WCAG 2.2** : niveau AA — [w3.org/TR/WCAG22](https://www.w3.org/TR/WCAG22/), critères 1.4.1 (usage de
  la couleur), 1.4.3 (contraste minimum), 1.4.11 (contraste du contenu non textuel), 2.4.7 (focus visible),
  2.4.11 (focus non masqué), 2.5.5/2.5.8 (taille des cibles).
- **ADR-0018** : intégration CSS Bootstrap/Skote compilé + custom properties, sans build Sass (décision
  d'architecture actée, non remise en cause par ce document).
- **EPIC-012 / US-061** : `project-management/backlog/epics/EPIC-012-integration-design.md`,
  `project-management/backlog/user-stories/US-061-charte-design-system.md`.

---

**Date de création :** 2026-09-02
**Auteur :** UI Designer (agent), pour EPIC-012 / US-061
