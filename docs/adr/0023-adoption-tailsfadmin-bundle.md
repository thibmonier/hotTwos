# ADR-0023 — Externalisation du socle de thème dans `tailsfadmin/tailsfadmin-bundle` ; complète ADR-0019

- **Statut :** Proposé (2026-09-09) — **brouillon en revue** ; complète **[ADR-0019](./0019-frontend-tailwind-assetmapper.md)** (n'en change pas le moteur Tailwind, en externalise le thème)
- **Réf. CDC :** prolonge ADR-0005 (présentation Twig + Stimulus + Turbo, AssetMapper) et ADR-0019 (Tailwind v4 CSS-first, sans Node.js)
- **Portée :** chantier technique préalable de **EPIC-013** (recensement & conception UX) — *bundle d'abord* (arbitrage PO 2026-09-09)

## Contexte

ADR-0019 a posé Tailwind v4 comme socle de style, avec des composants **copiés** de TailAdmin et un thème
**embarqué dans le repo** : layout monolithique inline (`templates/base.html.twig`), tokens + classes
composants dans `assets/styles/tailwind.css`, contrôleurs Stimulus (`theme_toggle`, `sidebar_toggle`,
`tabs`) dans `assets/controllers/`. Ce thème vit donc **dans le produit** : chaque évolution (nouveau
composant, correctif d'accessibilité) est une modification du code applicatif, non réutilisable, et le
markup TailAdmin est recopié à la main.

Un bundle réutilisable, **`tailsfadmin/tailsfadmin-bundle`** (thème admin Symfony basé sur TailAdmin,
**MIT**), a été publié sur Packagist (v1.1.0). Il fournit clés-en-main ce qui est aujourd'hui embarqué :

- **2 layouts** Twig namespacés — `@Tailsfadmin/layout/admin.html.twig` et `.../auth.html.twig` ;
- un **catalogue de composants** `<twig:tsf:…>` (UX Twig Components) : `Ui:*` (Card, Table, TableAdvanced,
  Modal, Dropdown, Alert, Badge, Button, Avatar…), `Form:*` (Input, Select, Datepicker, Toggle, Upload…),
  `Chart:*` (Bar, Line, VectorMap), `Layout:*` (Sidebar, Header, Breadcrumb), `Calendar` ;
- un **thème CSS** `assets/styles/theme.css` (tokens `@theme`, dark mode par classe, classes composants)
  branché en **un `@import`** dans l'entrée Tailwind de l'app — compilé par le **même** binaire autonome
  `symfonycasts/tailwind-bundle` (aucun Node) ;
- **15 contrôleurs Stimulus** (theme, sidebar, modal, dropdown, submenu, apexcharts, calendar, datepicker,
  dropzone, vectormap…) et une commande `tailsfadmin:assets:install` qui **vendore localement** les libs JS
  tierces (ApexCharts, flatpickr, FullCalendar, jsvectormap, Dropzone) — **sans CDN au runtime** ;
- un **menu de sidebar déclaratif** (`config/packages/tailsfadmin.yaml`), l'**i18n** du chrome (fr/en/es/de/ar)
  et un **thème de formulaire** Symfony.

Installer le bundle (`composer require`) l'active via Flex, ajoute `symfony/ux-twig-component`,
`symfony/form`, `symfony/translation`, et dépose `config/packages/{csrf,translation,twig_component}.yaml`.

## Décision

1. **Adopter `tailsfadmin/tailsfadmin-bundle` (pin `^1.1`) comme socle de thème externalisé.** Le produit
   ne porte plus de code de thème réutilisable : layout, composants, tokens et contrôleurs de chrome
   proviennent du bundle. Les mises à jour du thème passent par **Composer** (SemVer).
2. **Layouts :** les templates de l'app étendent `@Tailsfadmin/layout/admin.html.twig` (écrans applicatifs)
   et `@Tailsfadmin/layout/auth.html.twig` (login, mot de passe oublié). `templates/base.html.twig`
   monolithique est retiré au profit du layout du bundle + blocs applicatifs.
3. **CSS :** l'entrée Tailwind de l'app (`assets/styles/tailwind.css`) importe `theme.css` du bundle ; le
   rebranding se fait via les tokens `--color-brand-*` **après** l'import. Les **hooks CSS assertés par les
   tests** (`.flash-error`, `.flash-success`, `dialog.summary-dialog`, `.status-badge*`, `.visually-hidden`)
   restent définis côté app tant qu'ils ne sont pas couverts par des composants du bundle.
4. **Menu de sidebar :** déclaré dans `config/packages/tailsfadmin.yaml`. **Point ouvert (à valider en
   conception) :** le filtrage par permissions (`is_granted`) de la nav actuelle doit être préservé — soit
   via une contribution au menu builder du bundle, soit via un garde-fou applicatif. *À trancher avant
   l'intégration.*
5. **Assets :** déclarer les paths AssetMapper du bundle (`bundles/tailsfadmin`, `bundles/tailsfadmin-vendor`)
   et n'exécuter `tailsfadmin:assets:install` que lorsque des composants à dépendance tierce (Chart, Calendar,
   Datepicker, Upload) sont réellement employés. Build via `tailwind:build` (inchangé) puis `asset-map:compile`.
6. **Dark mode :** le bundle pilote le thème par **classe `.dark` sur `<html>`** ; l'app utilise
   `data-theme="dark"` (+ `data-bs-theme` résiduel). **Converger vers la stratégie du bundle** (adopter son
   `theme_controller`, retirer `theme_toggle_controller.js` et le résidu `data-bs-theme`).
7. **Plateforme :** le bundle requiert **PHP ≥ 8.5** ; aligner `composer.json` (`>=8.4` → `>=8.5`, déjà
   satisfait par l'image Docker prod).
8. **Non-régression :** `make ci` vert (cs, rector, phpstan max, deptrac, tests) + recette navigateur
   re-passée sur les écrans migrés ; les hooks de test et le plancher WCAG 2.2 AA sont préservés.

## Conséquences

### Positives
- **Zéro code de thème dans le produit** : le socle (layout, composants, tokens) est versionné et mis à jour
  via Composer ; fin de la recopie manuelle de markup TailAdmin.
- **Bibliothèque de composants riche** immédiatement disponible (`<twig:tsf:…>`) — accélère la conception
  d'EPIC-013 et le mapping pages ↔ composants (US-083).
- **i18n du chrome** et thème de formulaire fournis ; **libs JS vendorisées** (pas de CDN, conforme règle 11).
- **Réutilisable** sur d'autres produits ; sépare proprement « thème » et « métier ».

### Négatives / points de vigilance
- **Filtrage des permissions de la nav** (`is_granted`) à ré-implémenter sur le menu déclaratif — risque
  fonctionnel/sécurité si négligé (**point ouvert n°4**).
- **Changement de stratégie dark mode** (`.dark` vs `data-theme`) — toucher au contrôleur de thème et
  vérifier l'anti-FOUC.
- **Refonte du layout monolithique** `base.html.twig` (aucune abstraction en place) — les ~35 templates qui
  l'étendent doivent basculer sur le layout du bundle et ses blocs.
- **Couplage au rythme de release du bundle** (SemVer) ; surface de dépendances élargie (form, translation,
  ux-twig-component). Auditer les mises à jour (règle 11).
- **Migration des hooks CSS** assertés par les tests à planifier (garder côté app d'abord, migrer ensuite).

## Alternatives écartées
- **Statu quo (thème embarqué, ADR-0019 seul)** : duplication du markup TailAdmin, évolutions non
  réutilisables, dette de style au fil des modules.
- **Forker/recopier TailAdmin manuellement en composants app** : même bénéfice de composants, mais charge de
  maintenance interne et pas de mises à jour Composer.

## Suite
- Story de migration (chantier 1) : déclarer les paths AssetMapper, importer `theme.css`, migrer `login`
  + `/mon-compte` sur le layout `auth`/`admin` comme **pilote**, résoudre le menu `is_granted`, converger le
  dark mode, retirer `base.html.twig` monolithique, re-valider `make ci` + recette. Puis EPIC-013 conçoit
  sur ce socle figé.
