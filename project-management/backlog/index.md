# Backlog Index — HotOnes

> Dernière mise à jour: 2026-09-02
> Source de vérité fonctionnelle : `project-management/cdc/` · PRD : `project-management/prd.md`

---

## Résumé Global

| Type | 🔴 To Do | 🟡 In Progress | ⏸️ Blocked | 🟢 Done | Total |
|------|----------|----------------|------------|---------|-------|
| EPICs | 13 | 0 | 0 | 0 | 13 |
| User Stories | 48 | 0 | 0 | 0 | 48 |
| Tasks | 0 | 0 | 0 | 0 | 0 |

**Périmètre détaillé :** Sprint 0 (fondations) + Lot 1 (Walking Skeleton + modules REF, PRJ, TMP). Lots 2 à 5 au niveau EPIC (à affiner par lot). Total exigences CDC : 248 EF.
**Story points détaillés : 206 pts** — socle 57 (Sprint 0 : 28 · Sprint 1 : 29) · REF 51 · PRJ 45 · TMP 53.

---

## EPICs

| ID | Nom | Module | Lot | Statut | Prio | US | Points |
|----|-----|--------|-----|--------|------|-----|--------|
| EPIC-000 | Socle & Walking Skeleton | transverse | 0/1 | 🔴 | M | 11 | 68 |
| EPIC-001 | Référentiels et paramétrage | REF | 1 | 🔴 | M | 11 | 51 |
| EPIC-002 | Projets et delivery | PRJ | 1 | 🔴 | M | 9 | 45 |
| EPIC-003 | Temps et activité | TMP | 1 | 🔴 | M | 11 | 53 |
| EPIC-004 | Planification et staffing | PLN | 2 | 🔴 | S | À affiner | — |
| EPIC-005 | Finance et rentabilité | FIN | 2 | 🔴 | M/S | À affiner | — |
| EPIC-006 | Avant-vente / CRM | CRM | 3 | 🔴 | S | À affiner | — |
| EPIC-007 | Pilotage et reporting | PIL | 3 | 🔴 | S | À affiner | — |
| EPIC-008 | RH et cycle de vie | RH | 4 | 🔴 | S | À affiner | — |
| EPIC-009 | Recrutement | REC | 4 | 🔴 | S/C | À affiner | — |
| EPIC-010 | Socle IA mutualisé | transverse | 1→3 | 🔴 | M/S | 1re brique : US-053 | — |
| EPIC-011 | Industrialisation SaaS | transverse | 5 | 🔴 | S | À affiner | — |
| EPIC-012 | Intégration du design et de l'ergonomie | UX | transverse | 🔴 | H/M | 6 | ~31 |

> Dépendances : voir `dependencies-matrix.md`. Ordre de données strict lots 1→2→3 (non parallélisables).

---

## Chantier actuel : EPIC-012 — Intégration du design et de l'ergonomie 🔄 conception faite

**But :** poser le design system (thème Skote + tokens) et l'ergonomie définitive **avant** d'étendre le développement front (chantier transverse, fast-track, hors cadence sprint).
**Détail :** `backlog/epics/EPIC-012-integration-design.md` · **PR :** [#12](https://github.com/thibmonier/hotTwos/pull/12)

| US | EPIC | Points | Statut |
|----|------|--------|--------|
| US-061 Charte & design system (tokens + composants Skote) | EPIC-012 | 5 | 🔴 |
| US-062 Conception UX/UI des écrans du lot 1 (maquettes validées) | EPIC-012 | 5 | 🔴 |
| US-063 Intégration du layout Skote sur le socle Twig/Stimulus | EPIC-012 | 5 | 🔴 |
| US-064 Reskin des écrans livrés (dont F-S5-4, F-S5-5) | EPIC-012 | 8 | 🔴 |
| US-065 Audit & conformité accessibilité (WCAG 2.2 AA) | EPIC-012 | 5 | 🔴 |
| US-066 Recette d'ergonomie et validation utilisateurs | EPIC-012 | 3 | 🔴 |
| US-069 Correctifs d'ergonomie mineurs (suites recette US-066) | EPIC-012 | 2 | 🔴 |
| **Total indicatif** | | **~33** | |

> Conception livrée : ADR-0018 (CSS Skote compilé + tokens, sans build Sass), `architecture/design-system.md` (contrastes WCAG AA vérifiés), `architecture/ux-conception-lot1.md`, maquettes `architecture/design-canvas/` (canevas claude-design). Décisions : F-S5-4 → e-mail, statuts texte+icône+couleur. À trancher en US-063 : breakpoint 640/768 px, chargement Poppins.

### Sprints livrés

| Sprint | But | Points | Statut |
|--------|-----|--------|--------|
| Sprint 0 | Fondations & outillage | 28 | ✅ |
| Sprint 1 | Walking Skeleton (multi-tenant, auth/RBAC, analytique) | 29 | ✅ merged (#2) |
| Sprint 2 | Consolidation technique (migrations, RLS runtime, worker, obs.) | 20 | ✅ merged (#3) |
| Sprint 3 | Première saisie de temps (saisie ≤2min, validation par lot, RLS prod) | 23 | ✅ merged (#4) |
| Sprint 4 | Valorisation automatique (org → taux → valorisation figée, marge) | 21 | ✅ merged (#6) |
| Sprint 5 | Complétude & clôture du cycle temps (EPIC-003 achevé) | 22 | ✅ merged |
| Sprint 6 | Projets & delivery (cycle de vie, structure, affectation, clôture — EPIC-002) | 21 | ✅ merged (#8) |

**Sprint 7 planifié** (`sprints/sprint-007-design-system/`) : EPIC-012 D1→D4 (US-061/062/063/064, 23 pts) — but « design posé et appliqué au lot 1 ». Prochaine étape : merger la PR #12, puis décomposer le Sprint 7 (`/project:decompose-tasks 007`).

---

## Backlog Priorisé

### EPIC-000 — Socle & Walking Skeleton (68 pts)

| US | Titre | Points | Sprint | Prio |
|----|-------|--------|--------|------|
| US-006 | Squelette Symfony 8 + FrankenPHP worker + architecture | 8 | 0 | M |
| US-007 | Environnement conteneurisé + données de test | 5 | 0 | M |
| US-004 | Chaîne CI/CD et exécution en mode worker | 5 | 0 | M |
| US-008 | Staging, secrets et observabilité de base | 5 | 0 | M |
| US-009 | Outillage qualité/sécurité + conventions agent | 5 | 0 | M |
| US-001 | Fondation multi-tenant et isolation des données | 8 | 1 | M |
| US-002 | Authentification et cycle de vie des utilisateurs | 5 | 1 | M |
| US-003 | Rôles et habilitations (RBAC + périmètre de données) | 8 | 1 | M |
| US-005 | Modèle analytique en étoile et non-divergence | 8 | 1 | M |
| US-067 | Enrichissement du profil utilisateur (nom et prénom) | 3 | Backlog | M |
| US-068 | Écrans web d'authentification (login, mot de passe oublié, déconnexion, changement mdp) | 8 | Backlog | M |

### EPIC-001 — Référentiels et paramétrage (51 pts)

| US | Titre | Points | Prio |
|----|-------|--------|------|
| US-010 | Structure organisationnelle et rattachements historisés | 5 | M |
| US-011 | Référentiel de profils avec coûts et taux historisés | 8 | M |
| US-012 | Calendriers de travail et types d'absence | 5 | M |
| US-013 | Référentiel de compétences structuré | 3 | S |
| US-014 | Référentiel comptes clients et contacts | 3 | M |
| US-015 | Taux de vente et règle de priorité | 5 | M |
| US-016 | Devises et taux de change | 3 | M |
| US-017 | Statuts et circuits de validation paramétrables | 8 | M |
| US-018 | Seuils d'alerte paramétrables | 3 | S |
| US-019 | Ouverture de tenant et time-to-value < 15 min | 5 | M |
| US-020 | Journal d'audit du paramétrage | 3 | S |

### EPIC-002 — Projets et delivery (45 pts)

| US | Titre | Points | Prio |
|----|-------|--------|------|
| US-030 | Création de projet et cycle de vie | 5 | M |
| US-031 | Structure en lots et jalons | 5 | M |
| US-032 | Projets internes non facturables | 3 | S |
| US-033 | Budget bidimensionnel charge/montant et avenants | 8 | M |
| US-034 | Engagements externes rattachés au projet | 3 | M |
| US-035 | Avancement physique et reste à faire | 5 | M |
| US-036 | Vue d'atterrissage et détection de dérive | 8 | M |
| US-037 | Affectation et restriction d'imputation | 5 | M |
| US-038 | Clôture opérationnelle du projet | 3 | M |

### EPIC-003 — Temps et activité (53 pts)

| US | Titre | Points | Prio |
|----|-------|--------|------|
| US-050 | Saisie d'imputation hebdomadaire et quotidienne | 5 | M |
| US-051 | Saisie d'une semaine nominale en ≤ 2 min (🔴 bloquant lot 1) | 8 | M |
| US-052 | Saisie quotidienne sur mobile | 3 | S |
| US-053 | Pré-remplissage assisté depuis le plan (IA) et confirmation | 5 | M |
| US-054 | Déclaration, validation et compteurs d'absences | 5 | M |
| US-055 | Validation des temps par lot | 5 | M |
| US-056 | Relances automatiques de retard de saisie | 3 | M |
| US-057 | Clôture de période et traçabilité des modifications | 5 | M |
| US-058 | Tableau de bord de complétude de saisie | 3 | S |
| US-059 | Synthèse d'activité et planning depuis la saisie | 3 | S |
| US-060 | Valorisation automatique après validation (≤ 15 min) | 8 | M |

### EPIC-012 — Intégration du design et de l'ergonomie (~31 pts, fast-track)

| US | Titre | Points | Prio |
|----|-------|--------|------|
| US-061 | Charte et design system (design.md → tokens + composants Skote) | 5 | M |
| US-062 | Conception UX/UI des écrans du lot 1 (maquettes validées) | 5 | M |
| US-063 | Intégration du layout Skote sur le socle Twig/Stimulus | 5 | M |
| US-064 | Reskin des écrans livrés selon les maquettes | 8 | M |
| US-065 | Audit et mise en conformité accessibilité (WCAG 2.2 AA) | 5 | M |
| US-066 | Recette d'ergonomie et validation utilisateurs | 3 | M |

> Ordre conseillé : US-061 → US-062 → US-063 → US-064, puis US-065 et US-066. Conception (US-062) prérequis du reskin (US-064) — consigne PO « UX/UI avant dev front ».

---

## Légende Statuts

| Icône | Statut | Description |
|-------|--------|-------------|
| 🔴 | To Do | Pas encore commencé |
| 🟡 | In Progress | En cours de réalisation |
| ⏸️ | Blocked | Bloqué par un obstacle |
| 🟢 | Done | Terminé |

### Workflow

```
🔴 To Do ──→ 🟡 In Progress ──→ 🟢 Done
     │              │
     │              ↓
     └────→ ⏸️ Blocked ←────┘
                │
                ↓
           🟡 In Progress
```
