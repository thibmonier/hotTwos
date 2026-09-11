# Mapping pages ↔ composants tailsfadmin (+ gaps) — `page-component-mapping.md`

> **Livrable US-083** (Sprint 19 — EPIC-013). Associe chaque page à son layout + ses composants du socle, et formalise les *gaps*.
> **Sources** : `page-inventory.md` (pages), `parcours-personas.md` (priorités), bundle `tailsfadmin` **v1.4.2** (inspection `vendor/.../templates/components/`).
> **Statut** : 🟡 Draft — en attente de **validation PO**.
> **Mis à jour** : 2026-09-11.

## Composants réels du socle (v1.4.2)
- **Layouts** : `@Tailsfadmin/layout/admin.html.twig` (sidebar + header surchargeables) · `auth`.
- **Ui** : `Alert` `Avatar` `Badge` `Button` `Card` `MediaCard` `GridImage` `Dropdown` `Modal` `Preloader` `ProgressBar` `Ribbon` `Table` `TableAdvanced` `Tabs` `Video`.
- **Form** : `Input` `InputGroup` `Select` `Textarea` `Checkbox` `Radio` `Toggle` `Datepicker` `Upload` (+ form theme Symfony).
- **Layout** : `Header` `Sidebar` `Breadcrumb`. · **Chart** : `Line` `Bar` `VectorMap`. · `Calendar`.
- **Contrôleurs Stimulus** : `tailsfadmin--{tabs, clipboard, kanban, sidebar, submenu, modal, dropdown, theme, otp, datepicker, dropzone, apexcharts, calendar, vectormap}`.

> Syntaxe : `<twig:tsf:Ui:Card>`, `<twig:tsf:Form:Input>`, `<twig:tsf:Layout:Breadcrumb>`, `<twig:tsf:Chart:Line>`, `<twig:tsf:Calendar>`.

---

## 1. Mapping par **type de page** (patrons réutilisables)

> Chaque page hérite du patron de son type (colonne « Type » de `page-inventory.md`). Les spécificités par domaine sont au §2.

| Type | Layout | Composants socle mobilisés | Gaps |
|------|--------|----------------------------|------|
| **Dashboard** | admin | `Layout:Breadcrumb`, en-tête contextuel, `Ui:Card` (blocs), `Ui:ProgressBar`, `Chart:Line`/`Bar` (ApexCharts), `Ui:TableAdvanced` (liste allégée), `Ui:Badge`, `Ui:Dropdown` (filtres période) | **G1 `Ui:StatCard`** (KPI : icône + valeur + variation), **G4 `Layout:PageHeader`** (bandeau titre + KPI contextuels) |
| **Liste** | admin | `Layout:Breadcrumb`, `Ui:TableAdvanced` (tri/pagination), `Ui:Badge` (statuts), `Ui:Button` (créer), `Form:Input`/`Select` (recherche/filtres) | **G3 `Ui:FilterBar`** (barre recherche + filtres si non couverte par `TableAdvanced`) |
| **Détail (simple)** | admin | `Layout:Breadcrumb`, `Ui:Card`, `Ui:Badge`, `Ui:Button`, `Ui:Modal` (édition), form theme | – |
| **Détail (complexe)** | admin | idem + **`Ui:Tabs`** (navigation thématique, `tailsfadmin--tabs`) + en-tête contextuel | **G4 `Layout:PageHeader`**, **G5 `Ui:Timeline`/`Stepper`** (cycle de vie) |
| **Création / Formulaire** | admin | `Layout:Breadcrumb`, `Form:*` (Input/Select/Textarea/Checkbox/Radio/Toggle/Datepicker/Upload), `Ui:Button`, `Ui:Alert` (erreurs) | – |
| **Config** | admin | `Layout:Breadcrumb`, `Form:*`, `Ui:Card`, `Ui:Button`, `Ui:Alert` | – |
| **Kanban** | admin | `tailsfadmin--kanban` (contrôleur), `Ui:Card` (cartes), `Ui:Avatar`, `Ui:Badge` | **G2 `Ui:Kanban`/`KanbanColumn`** (composant Twig manquant — seul le contrôleur existe) |
| **Auth** | auth | `Form:Input`, `Ui:Button`, `Ui:Alert`, `tailsfadmin--otp` (code) | – |
| **Système (erreurs 403/404/500)** | admin | `Ui:Alert`, `Ui:Button` (retour) | **G6 `Ui:EmptyState`** (illustration + message + action) |

---

## 2. Mapping par domaine (spécificités & pages notables)

### Dashboards (DSH-*)
- Tous : `admin` + `Breadcrumb` + `PageHeader (G4)` + grille de `Card`/`StatCard (G1)` + `Chart:Line/Bar` + `TableAdvanced` (liste allégée) + `Dropdown` (période).
- **DSH-AGENCE** (P6) : drill-down cliquable (chaque KPI → page source) — comportement à concevoir (pas un composant : liens depuis `StatCard`).
- **DSH-REC** (P5) : aperçu `Kanban (G2)`.

### Cycle commercial & projet
- **PG-CLI-01 / PG-DEV-01 / PG-PRJ-01 / PG-FAC-01 (Listes)** : `TableAdvanced` + `Badge` (statuts) + `Button` créer + `FilterBar (G3)`.
- **PG-CLI-02 / PG-DEV-02 / PG-PRJ-02 / PG-FAC-02 (Détails)** : `Tabs` + `Card` + `PageHeader (G4)` contextuel (CA client, montant devis, finance projet). PG-PRJ-02 **déjà** sur `Tabs` (référence).
- **PG-*-03 (Créations)** : `Form:*` + form theme + `Alert`.
- Bandeau **cycle de vie** (Client→Devis→Projet→Facture) sur les fiches → `Timeline/Stepper (G5)`.

### Cycle RH
- **PG-REC-01 Pipeline recrutement** : `Kanban (G2)` + `Card` (candidat) + `Avatar` + `Badge`.
- **PG-CAR-02 Fiche collaborateur** : `Tabs` (contrat/compétences/absences/entretiens) — **HAB-2** : onglet entretien à accès restreint (voir §4).
- **PG-EMB-* Contrats** : `TableAdvanced`, `Datepicker` (échéances), `Upload` (pièces).

### Pilotage & finance
- **PG-VAL-01 / PG-FIN-01** : `Card`/`StatCard (G1)` + `ProgressBar` + `Chart:Line/Bar` + `TableAdvanced`. (déjà partiellement : ProgressBar adoptée en US-086.)
- **PG-FIN-02…05 Config** : `Form:*` + `Card`.

### Planification
- **PG-STF-02 Plan de charge** : grille capacité/charge → `TableAdvanced` ; visualisation calendaire éventuelle → `Calendar`. Vue heatmap capacité → **G7 `Ui:Heatmap`** (Could).

---

## 3. Couverture des pages
Toutes les pages de `page-inventory.md` relèvent d'un des **types** du §1 (colonne « Type »), donc sont mappées à un layout + un jeu de composants **ou** à un gap explicite. Aucune page sans layout.

| Layout | Pages concernées |
|--------|------------------|
| `auth` | PG-AUTH-01/02/03/04 |
| `admin` | toutes les autres (dashboards, listes, détails, créations, config, kanban, système) |

---

## 4. Gaps — demandes d'évolution `tailsfadmin` (backlog bundle)

| ID | Composant proposé | Usage | Pages concernées | Priorité bundle | WCAG 2.2 AA |
|----|-------------------|-------|------------------|-----------------|-------------|
| **G1** | `Ui:StatCard` | Carte KPI (icône + valeur + variation/delta + lien) | Tous les dashboards, en-têtes finance/projet | **Must** | Contraste delta ↑/↓ pas uniquement par couleur (icône + signe) |
| **G2** | `Ui:Kanban` / `Ui:KanbanColumn` | Composant Twig enveloppant le contrôleur `tailsfadmin--kanban` | PG-REC-01, DSH-REC | **Must** | Alternative clavier « Déplacer vers… » + `aria-live` (déjà dans le contrôleur) |
| **G3** | `Ui:FilterBar` | Barre recherche + filtres au-dessus d'une liste (si non couvert nativement par `TableAdvanced`) | Toutes les listes | **Should** | Labels explicites, focus, effacement des filtres |
| **G4** | `Layout:PageHeader` | Bandeau d'en-tête : titre + `Breadcrumb` + slots KPI contextuels | Toutes les pages (convention transverse) | **Should** | Structure de titres correcte (h1), repère de position |
| **G5** | `Ui:Timeline` / `Ui:Stepper` | Visualiser un cycle de vie (Client→Devis→Projet→Facture ; Offre→…→Carrière) | Fiches des deux cycles + dashboards | **Should** | Étape courante annoncée (aria-current) |
| **G6** | `Ui:EmptyState` | État vide (illustration + message + action) | Listes, dashboards, erreurs 404 | **Could** | Action clavier, texte alternatif |
| **G7** | `Ui:Heatmap` | Vue de densité (capacité/charge) | PG-STF-02 | **Could** | Valeurs lisibles hors couleur (tooltip + texte) |

> **Note** : G1/G3/G4/G5 sont **assemblables** dans l'immédiat à partir de `Card`/`Table`/`Breadcrumb` — les créer en composants dédiés mutualise le style et évite la dérive (design system). G2 est le plus prioritaire fonctionnellement (seul le contrôleur existe, pas le composant Twig).

## 5. Adoption côté app (hors gaps bundle)
- **Breadcrumb** (`Layout:Breadcrumb`) : composant **existant** mais **non encore adopté** dans l'app → tâche d'adoption transverse (convention US-080 §1).
- **Layout admin surchargé** : la sidebar/header de l'app sont déjà branchés sur le socle (US-086).

## 6. Validation PO
- [ ] Chaque page mappée (via son type) à un layout + composants OU gap explicite.
- [ ] Liste des gaps (G1–G7) et priorités validées (entrée d'US-085 : effort reskin).
- [ ] Feu vert pour démarrer US-085 (backlog reskin priorisé).
