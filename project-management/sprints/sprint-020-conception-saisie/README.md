# Sprint 20 — Conception UX du parcours de saisie (EPIC-003)

**Période** : 2026-09-14 → 2026-09-25 (10 j ouvrés) · **Capacité** : 13 pts · **EPIC-013 : bouclage**

## Objectif
Auditer et concevoir en maquettes haute-fidélité **validées PO** le parcours de saisie collaborateur
(EPIC-003, persona P1 = 80 % des users), prêtes pour le dev reskin au Sprint 21.
Respecte la règle produit : **maquettes avant dev front**. Débloqué par l'accès `hotones` (2026-09-11).

## Contenu
| Story | Points | Livrable |
|-------|--------|----------|
| US-082 — Audit & critique UX de l'existant (inspiré hotones) | 5 | `architecture/audit-ux-existant.md` |
| US-084 — Maquettes haute-fidélité des écrans prioritaires | 8 | `architecture/design-canvas/*.dc.html` + `VALIDATION.md` |

## Écrans prioritaires (parcours saisie EPIC-003)
- **Dashboard collaborateur** — *création* (pas de dashboard produit ; home hotones = réf. faible ; maquette US-084 seule).
- Saisie hebdomadaire · Saisie du jour · Mes absences · Complétude — *reskin amélioré* (audit US-082 + maquette US-084).

## Fichiers du sprint
- `sprint-goal.md` — Sprint Goal, DoD, backlog, dépendances, risques, cérémonies
- `task-board.md` — Kanban + burndown
- `daily-notes/` — notes quotidiennes
- `sprint-review.md` / `sprint-retro.md` — à créer en clôture

## Prérequis J1
Déposer les **captures hotones** dans `architecture/design-canvas/hotones-ref/` (PO).
Dépôt en cours (convention : `hotones-ref/README.md`) ; home collaborateur = réf. faible ; dashboards de cycle de vie = réf. sprints ultérieurs.

## Suite (S21)
Dev reskin EPIC-003 : lot bundle **G1 `Ui:StatCard` + G4 `Layout:PageHeader`**, puis portage des écrans.
