# Task Board — Sprint 7 (Design system, EPIC-012)

## Légende
🔲 À faire · 🔄 En cours · 👀 En review · ✅ Terminé · 🚫 Bloqué

> **⚠️ Pivot ADR-0018 → ADR-0019 (2026-09-02) — board réconcilié.** La direction technique du sprint a
> changé en cours de route : abandon de **Bootstrap précompilé + thème Skote** (ADR-0018) au profit de
> **Tailwind CSS v4 via `symfonycasts/tailwind-bundle`** (binaire autonome, **sans Node.js**, AssetMapper —
> ADR-0019, supersede ADR-0018). Raisons : poids (305 KB Bootstrap non purgé → **17 KB** purgé, ~18×), deux
> systèmes en tension (classes Bootstrap + tokens overridés élément par élément), personnalisation Sass
> bloquée, **risque licence Skote (ThemeForest)** éliminé (TailAdmin = MIT).
>
> **Conséquence sur ce board** : les tâches libellées « Bootstrap/Skote compilé », « Layout Skote » ont été
> **atteintes autrement** (équivalent Tailwind). Le sprint est en réalité **quasi terminé** — l'essentiel a
> été livré et **mergé (PR #14)**, pas à 0 % comme l'affichait l'ancien board. Commits de référence :
> `d4f9673` (fondation Tailwind + layout + écran complétude), `cc96fc2` (reskin des 14 écrans restants),
> `a64b5ae` (fix a11y post-migration), `2c07fe2` (build Tailwind en CI).

## 🔲 À Faire

| ID | US | Tâche (réalité Tailwind) | Est. | Note |
|----|-----|--------------------------|------|------|
| T-061-07b | US-061 | **Resynchroniser `design-system.md`** sur la réalité Tailwind (retirer Skote/Bootstrap, refléter `@theme`) | 2h | Doc drift réel : le fichier réf. encore Skote/Bootstrap (52 occ.), jamais mis à jour depuis la conception. C'est la « suite » de l'ADR-0019 (réinjecter design-system.md dans @theme). |
| T-063-06 | US-063 | Revue `symfony-reviewer` du layout Tailwind post-migration | 1h | Pas de trace de revue dédiée après la bascule. |
| T-064-08 | US-064 | Revue `symfony-reviewer` des écrans reskinnés | 2h | Idem — revue de clôture. |
| T-062-01 | US-062 | *(Optionnel)* Finaliser maquettes hi-fi écrans restants | 4h | **Rendu optionnel** par le gate PO levé (direction pré-approuvée). |
| T-062-02 | US-062 | *(Optionnel)* Déclinaisons mobile + états | 3h | Idem — optionnel. |

## 🔄 En Cours
| ID | US | Tâche | Démarré |
|----|-----|-------|---------|
| — | — | — | — |

## 👀 En Review
| ID | US | Tâche | Reviewer |
|----|-----|-------|----------|
| — | — | — | — |

## ✅ Terminé
| ID | US | Tâche (réalité Tailwind) | Justificatif |
|----|-----|--------------------------|--------------|
| T-061-01 | US-061 | tokens (couleurs/typo/espacements) → variables CSS clair/sombre | `tailwind.css` `:root` + `[data-theme=dark]` (valeurs AA US-065) — `d4f9673` |
| T-061-02 | US-061 | ~~Bootstrap/Skote compilé~~ → **binaire Tailwind autonome** (AssetMapper, sans Node) + Poppins | `symfonycasts/tailwind-bundle`, `config/packages/symfonycasts_tailwind.yaml` — `d4f9673` |
| T-061-03 | US-061 | Migrer `app.css`/`tokens.css`/`components.css`/`layout.css` → `@theme` | 4 CSS supprimés → `tailwind.css` `@theme` — `d4f9673` |
| T-061-04 | US-061 | Composants tokenisés | `@layer components` (`.status-badge`, `.flash-*`, `dialog.summary-dialog`) + banque TailAdmin (MIT) — `d4f9673` |
| T-061-05 | US-061 | Page styleguide | `templates/styleguide/index.html.twig` reskin — `cc96fc2` |
| T-061-06 | US-061 | Contrastes axe-core + non-régression | Re-audit axe-core (9 écrans, clair) = **0 violation** — `a64b5ae`. *Audit complet AA + sombre = US-065 (S8).* |
| T-062-03 | US-062 | Registre de validation | `ux-conception-lot1.md` (PR #12) |
| T-062-04 | US-062 | Revue a11y + validation PO (gate) | **Gate levé PO 2026-09-02** (direction design pré-approuvée pour tout le lot 1) |
| T-063-01 | US-063 | ~~Layout Skote~~ → **layout Tailwind** (`base.html.twig` : sidebar/topbar) | `templates/base.html.twig` (+350/-) — `d4f9673` |
| T-063-02 | US-063 | Navigation filtrée RBAC | nav `is_granted` via `PermissionVoter` dans `base.html.twig` — `d4f9673` |
| T-063-03 | US-063 | Responsive + breakpoint 640/768 | `@media (min-width:640px)` (dialog) + layout responsive — `d4f9673` |
| T-063-04 | US-063 | Bascule thème (Stimulus) | contrôleurs thème/sidebar conservés + `@custom-variant dark [data-theme=dark]` — `d4f9673` |
| T-063-05 | US-063 | Non-régression + recette | **417 tests verts** ; recette navigateur = US-066 (S8) |
| T-TECH-02 | dette | Budget assets (poids CSS) | Spike ADR-0019 : **17 KB purgé** vs 305 KB Bootstrap (~18×), poids stable |
| T-064-01 | US-064 | Reskin saisie (week/day) | `timesheet/week.html.twig` + `day.html.twig` — `cc96fc2` |
| T-064-02 | US-064 | Reskin complétude (F-S5-4/F-S5-5) | `completeness/index.html.twig` (écran de référence phase 1) — `d4f9673` |
| T-064-03 | US-064 | Reskin valorisation | `valuation/index.html.twig` — `cc96fc2` |
| T-064-04 | US-064 | Reskin projets + validation | `project/index|new|show` + `timesheet/validation` — `cc96fc2` |
| T-064-05 | US-064 | Reskin absences + relances | `absence/index` + `reminder/index` — `cc96fc2` |
| T-064-06 | US-064 | Reskin organisation + périodes + tarifs | `organization/index` + `period/index` + `pricing/index` — `cc96fc2`. *(« profils/admin » = pas d'écran Twig séparé à ce stade.)* |
| T-064-07 | US-064 | Non-régression + états | **417 tests verts** post-reskin — `cc96fc2` |
| T-TECH-01 | dette | sprintf→set_config (3 sites) — **déjà résolu** (param lié : `TenantSessionConfigurator:48`, `TenantContextMiddleware:67`, `DoctrineAnalyticsProjector:48`) | 2026-09-02 (vérifié) |

## 🚫 Bloqué
| ID | US | Raison | Action |
|----|-----|--------|--------|
| — | — | Aucun blocage | — |

> **Gate maquettes levé (PO 2026-09-02)** : direction design pré-approuvée pour **tous** les écrans du lot 1.
> Le reskin a été mené en appliquant directement la charte + le layout Tailwind, validation PO en lot.
> Plancher DoD conservé : WCAG 2.2 AA + états « sans-permission » sur les écrans à données sensibles.

## Métriques (réconciliées)
- **Tâches** : 27 total · **20 terminées** (74 %) · 3 à faire (revues + doc) · 2 optionnelles (maquettes hi-fi)
- **Cœur du sprint (US-061/063/064 + T-TECH)** : **livré et mergé (PR #14)** — tokens `@theme`, layout
  Tailwind, **15 écrans reskinnés**, budget assets tenu, tests verts.
- **Reliquat réel** (≈5h) : (1) resynchroniser `design-system.md` sur Tailwind ; (2) revues de clôture
  `symfony-reviewer` (layout + écrans).
- **Hors S7 (→ S8)** : US-065 audit a11y complet (AA + thème sombre ; seul un re-audit partiel fait) ·
  US-066 recette d'ergonomie.
