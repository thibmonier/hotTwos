# Évaluation d'un actif réutilisable — thème Skote (Symfony Admin)

**Projet :** HotOnes · **Date :** 2026-08-31 · **Statut :** évaluation d'opportunité (aucune reprise engagée)
**Cadre :** Scénario C — reprise sélective des acquis, sur **décision tracée** (`CDR-2`). Ce document est le support de cette décision.
**Emplacement de l'actif :** `project-management/Skote_Symfony_v2.2.0/` (327 Mo, non versionné dans le produit)

---

## 1. Ce que c'est

Thème **Skote — Symfony Admin Dashboard** (ThemeForest, item 36474775), version 2.2.0. Livré en 3 variantes : `Admin` (complet, 153 templates Twig), `Starterkit` (minimal), `Documentation`.

| Caractéristique | Valeur | Cible HotOnes (ADR) | Verdict |
|---|---|---|---|
| Framework | Symfony **7.1** | Symfony **8.1+** (`ADR-3`) | ⚠️ Montée de version requise |
| Build assets | **Webpack Encore** | **Symfony Reprise + Vite** (`ADR-5`) | 🔴 Divergence — config non réutilisable |
| CSS | **Bootstrap 5.3.3** + SCSS | Design system CDC produit en lot 0 (`cdc/11`) | ⚠️ Base générique, à subordonner aux `DP-*`/`AP-*` |
| JS interactif | **Stimulus 3** (+ stimulus-bridge) | Stimulus + **Turbo** (`ADR-5`) | ✅ Stimulus OK · ⚠️ Turbo absent |
| Rendu | Twig serveur | Twig serveur (`ADR-5`) | ✅ Aligné |
| Licence | **Proprietary / ThemeForest (Envato)** | Produit SaaS commercialisé (`HYP-2`) | 🔴 **À vérifier — voir §4** |

## 2. Contenu réutilisable (153 templates Twig)

- **Ossature admin** : layout (sidebar, topbar, breadcrumb), thème clair/sombre, RTL.
- **Authentification** : login, register, 2FA / two-step, lock screen, recover, email verification → utile pour **US-002**.
- **Composants de formulaire & inputs**, tables (DataTables), **charts** (ApexCharts, ChartJS, ECharts), **calendar** (FullCalendar), file manager, chat.
- **Pages métier proches** : `dashboard-saas`, `candidate-list` / `candidate-overview` (→ module Recrutement, lot 4), contacts, ecommerce (non pertinent).

## 3. Recommandation de reprise (sélective)

> Traiter le thème comme un **stock de composants candidats**, mobilisé en **lot 0** (constitution du design system et de l'ossature UI), **jamais comme socle**. Conforme à l'esprit du scénario C et à `CDR-2`.

**À reprendre (candidats, sous réserve du design system `cdc/11`)**
- L'**ossature de layout** (sidebar/topbar/breadcrumb) comme point de départ visuel.
- Les **écrans d'authentification** (US-002) : login, 2FA, lock screen, recover.
- Les **composants génériques** : formulaires, tableaux, cartes, graphiques (ApexCharts pour US-036 atterrissage, US-058 complétude, et le Pilotage lot 3), calendrier (US-054 absences).

**À NE PAS reprendre**
- Le **squelette Symfony 7.1** (on part d'un Symfony 8.1 neuf — `ADR-1`/`ADR-3`).
- La **chaîne Webpack Encore** (remplacée par Reprise + Vite — `ADR-5`).
- Bootstrap **comme design system final** : il sert de base technique, mais les principes de design du CDC (`DP-*`) et les anti-patterns (`AP-*`) **priment** (`cdc/11`).
- Les pages hors périmètre (crypto, ecommerce, blog).

**Point d'attention ergonomie — le cœur ne se reprend pas**
La **saisie de temps ≤ 2 min** (US-051, `ENF-UX-1`, critère bloquant du lot 1) est le différenciateur du produit. Elle doit être **conçue sur mesure** et testée auprès de vrais utilisateurs — un template de grille générique aide pour l'habillage, pas pour l'ergonomie de saisie. Ne pas laisser le thème dicter ce parcours.

## 4. ⚠️ Point bloquant à lever avant toute reprise — licence

La licence est **proprietary ThemeForest (Envato)**. Pour un **produit SaaS multi-tenant commercialisé** (`HYP-2`), la *Regular License* ThemeForest est généralement **insuffisante** (elle couvre un « single end product » non vendu à des utilisateurs multiples payants) ; une *Extended License* — ou un autre socle UI — peut être nécessaire.

- **Action** : vérifier la portée exacte de la licence détenue **avant** d'intégrer le moindre fichier du thème au dépôt produit. Relève de la conformité supply chain (`.claude/rules/11-security.md` §6, `ENF-SEC` supply chain) et rejoint le lot juridique (`CTR-3`, arbitrages `ARB-*`).
- Les **bibliothèques tierces embarquées** (DataTables, TinyMCE, PrismJS, Choices.js…) portent leurs propres licences (souvent MIT) — à auditer séparément si reprises.
- Tant que ce point n'est pas levé : usage **en interne comme référence visuelle uniquement**, aucune intégration au produit livrable.

## 5. Décision attendue (à tracer — `CDR-2`)

| Décision | Décideur | Statut |
|---|---|---|
| Utiliser Skote comme accélérateur d'ossature UI en lot 0 | Responsable technique | ⏳ à statuer |
| Vérifier/étendre la licence ThemeForest pour usage SaaS | Sponsor + conseil juridique | ⏳ **prérequis bloquant à la reprise** |
| Périmètre exact des composants repris | Responsable technique + designer | ⏳ à statuer (lot 0) |

---

**Documents liés :** `research-summary.md` (scénario C, `AUD-1`/`AUD-2`), `technical-options.md` (`ADR-5` build), `constraints.md` (supply chain), `cdc/11-criteres-design.md` (design system).
