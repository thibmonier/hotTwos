# Sprint Review — Sprint 24

**Thème** : Amorce EPIC-005 (finance) + suite EPIC-002 (clients) + enabler bouton pleinement adoptable
**Sprint Goal** : Étendre le socle reskin à la valorisation et au pilotage financier (amorce EPIC-005), poursuivre EPIC-002 par les clients, et rendre le composant bouton pleinement adoptable (pass-through d'attributs) — offrir aux personas direction/production un parcours financier cohérent, accessible et prêt à s'étendre.
**Date** : 2026-11-20

---

## Atteinte du Sprint Goal

**Verdict : ✅ Atteint**

Les quatre User Stories engagées (11/11 points) sont livrées et mergées sur `main` avec une Definition of Done complète. Le sprint applique l'action-clé de la rétro S23 (**ACTION-1**) en tête de sprint : le composant `tsf:Ui:Button` relaie désormais les attributs Stimulus, ce qui **achève la convergence a11y/composant** amorcée en S23. Il reconduit les deux réflexes gagnants (troisième sprint consécutif à 100 % DoD) : **avancer par incrément fini** (≤ 3 écrans, garde-fou ACTION-3) et **traiter la dette à la racine** (le composant du bundle plutôt que l'écran).

- **Enabler `Ui:Button` (US-100)** : le bundle **v1.6.2** ajoute le pass-through d'attributs (`{{ attributes }}`) ; les boutons **câblés Stimulus** des parcours saisie (complétude, validation des temps, relances, absences) sont migrés vers le composant. La dette de S23 (« bouton pas encore adoptable ») est **soldée** — focus visible WCAG 2.4.7 sur tout le parcours, via le composant.
- **Amorce EPIC-005 (US-101, US-102)** : la **valorisation** (`/valorisation`) et le **tableau de bord financier** (`/finance`) sont portés sur le socle tailsfadmin (tokens, `StatCard` G1, `PageHeader` G4), sans toucher aux services de calcul (US-071/072/073).
- **Suite EPIC-002 (US-103)** : la **liste des clients** (`/clients`) rejoint le socle avec filtre/recherche Stimulus sans rechargement (gap G3, pattern US-097).

L'incrément est fini et cohérent : les parcours financiers direction/production et l'entrée « clients » sont homogènes avec le reste de l'application, et deux EPICs (005 et 002) sont prêts à s'étendre au Sprint 25.

---

## US du Sprint 24

| ID | Titre | PR | Verdict | Tests |
|---|---|---|---|---|
| US-100 | Enabler `Ui:Button` — pass-through d'attributs + migration des boutons câblés | bundle v1.6.2 · app #152 | ✅ | `CompletenessPageTest` (pass-through `data-action`) ; suites saisie (complétude/validation/relances/absences) vertes ; focus 2.4.7 via composant |
| US-101 | PG-VAL-01 — reskin Valorisation | #153 | ✅ | Suite valorisation (accès + breakdown) verte ; assertions reskin |
| US-102 | PG-FIN-01 — reskin Tableau de bord financier | #154 | ✅ | `FinanceDashboardTest` (KPI + gating finance 403) |
| US-103 | PG-CLI-01 — reskin Liste des clients | #155 | ✅ | `ClientPageTest` (filtre/recherche Stimulus + bouton composant + gating création) |

**Légende** : ✅ Livré DoD complète · ⚠️ Livré avec dette · ❌ Non livré

---

## Chantiers rétro / techniques (hors points)

| Chantier | Origine | Statut |
|---|---|---|
| ACTION-2 — « Branche d'abord » | rétro S23 | ✅ Tenue — aucun commit sur `main` en local ce sprint |
| Suivi CodeQL `Analyze` | rétro S23 | ✅ Stable sur les exécutions du sprint (default-setup, non-requis) |
| ACTION-4 — arbitrage base de décompte du solde d'absences | report S22/S23 | ⏳ Reporté S25 (décision PO non tranchée) |

---

## Métriques Sprint 24

| Indicateur | Valeur |
|---|---|
| Points engagés | 11 pts (Must 9 + Should 2) |
| Points livrés (DoD stricte) | 11 pts |
| Vélocité Sprint 24 | 11 pts |
| Taux de complétion DoD stricte | 100 % |
| US livrées / engagées | 4 / 4 |
| PRs mergées | 4 hotTwos (#152, #153, #154, #155) + 1 release bundle (v1.6.2) |
| Suite de tests | 721 tests verts (`make ci` complet : cs · rector · phpstan max · deptrac 0 violation) |

Vélocité 11 pts, sous la fourchette récente (~13) comme prévu au planning pour absorber le travail cross-repo (bundle v1.6.2) — troisième sprint consécutif à `goal_met: true` en DoD stricte (S22–S24).

---

## Dette & Suivis

### ACTION-4 — base de décompte du solde d'absences (report S25)
Toujours ouvert : le solde d'impact (US-091b) est en jours ouvrés, le compteur persisté en span calendaire. À trancher (harmoniser vs documenter l'écart) — reporté au Sprint 25.

### Extension EPIC-005 (S25 pressenti)
L'amorce (valorisation + dashboard financier) est posée. Reste à porter sur le socle : PG-PRC-01 (profils & taux) et les configs finance (dérive, devises, FEC, périodes — PG-FIN-02…05), selon `backlog-reskin-priorise.md`.

---

## Démonstration

Parcours financier direction/production + entrée clients, sur le socle tailsfadmin.

### 1. Valorisation — US-101
- `/valorisation`. PageHeader + StatCards (CA reconnu / occupation…) et `ProgressBar` sur tokens ; breakdown préservé ; logique de valorisation inchangée.

### 2. Tableau de bord financier — US-102
- `/finance` (habilitation finance). PageHeader + StatCards (marge, dérive…) ; services US-071/072/073 inchangés ; 403 sans habilitation.

### 3. Liste des clients — US-103
- `/clients`. Tableau carte tailsfadmin + **recherche nom/SIREN sans rechargement** (contrôleur Stimulus `clients`, gap G3) ; bouton « Créer » via `tsf:Ui:Button` (focus visible) ; gating de création préservé.

### 4. Accessibilité — US-100
- Boutons **câblés** des parcours saisie (complétude, validation, relances, absences) portés par `tsf:Ui:Button` v1.6.2 : `data-action`/`data-*-target` relayés, focus clavier visible partout (2.4.7) via le composant.

---

*Sprint Review rédigée à partir des PRs mergées (#152–#155 + release bundle v1.6.2) — 2026-11-20.*
