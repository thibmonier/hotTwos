# Backlog Index — HotOnes

> Dernière mise à jour: 2026-08-31
> Source de vérité fonctionnelle : `project-management/cdc/` · PRD : `project-management/prd.md`

---

## Résumé Global

| Type | 🔴 To Do | 🟡 In Progress | ⏸️ Blocked | 🟢 Done | Total |
|------|----------|----------------|------------|---------|-------|
| EPICs | 12 | 0 | 0 | 0 | 12 |
| User Stories | 40 | 0 | 0 | 0 | 40 |
| Tasks | 0 | 0 | 0 | 0 | 0 |

**Périmètre détaillé :** Sprint 0 (fondations) + Lot 1 (Walking Skeleton + modules REF, PRJ, TMP). Lots 2 à 5 au niveau EPIC (à affiner par lot). Total exigences CDC : 248 EF.
**Story points détaillés : 206 pts** — socle 57 (Sprint 0 : 28 · Sprint 1 : 29) · REF 51 · PRJ 45 · TMP 53.

---

## EPICs

| ID | Nom | Module | Lot | Statut | Prio | US | Points |
|----|-----|--------|-----|--------|------|-----|--------|
| EPIC-000 | Socle & Walking Skeleton | transverse | 0/1 | 🔴 | M | 9 | 57 |
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

> Dépendances : voir `dependencies-matrix.md`. Ordre de données strict lots 1→2→3 (non parallélisables).

---

## Sprint Actuel : Sprint 0 — Fondations & Outillage ✅ composé

**Goal :** Installer le socle technique (squelette Symfony 8 mode worker + architecture, environnement conteneurisé, staging, outillage qualité/sécurité) afin que le Sprint 1 construise sur un socle sûr, reproductible et vérifié en continu. **Aucune valeur métier** — de la capacité de construire.
**Période :** 2026-08-18 → 2026-08-29 (10 j ouvrés) · **Détail :** `sprints/sprint-000-fondations/`
**Capacité (prévision) :** 28 points — à recalibrer (hypothèse d'équipe, cf. `ARB-20`).

| US | EPIC | Points | Statut |
|----|------|--------|--------|
| US-006 Squelette Symfony 8 + FrankenPHP worker + archi | EPIC-000 | 8 | 🔴 |
| US-007 Environnement conteneurisé + données de test | EPIC-000 | 5 | 🔴 |
| US-004 Chaîne CI/CD et exécution en mode worker | EPIC-000 | 5 | 🔴 |
| US-008 Staging + secrets + observabilité | EPIC-000 | 5 | 🔴 |
| US-009 Outillage qualité/sécurité + conventions agent | EPIC-000 | 5 | 🔴 |
| **Total engagé** | | **28** | |

> ⚠️ Prérequis non-logiciels en parallèle (hors backlog dev) : `AUD-1`/`AUD-2` (audit existant, peut réviser le scénario — `CDR-5`), design system (`cdc/11`), AIPD (`ENF-RGPD-5`), AI Act (`CTR-3`).

### Sprint suivant : Sprint 1 — Walking Skeleton applicatif (composé, 29 pts)

**Goal :** Charpente applicative — tenant isolé (barrière testée), auth + RBAC, modèle analytique reconstructible. Première saisie de temps engagée pour le Sprint 2.
US : US-001 (8), US-002 (5), US-003 (8), US-005 (8) · **Détail :** `sprints/sprint-001-walking_skeleton/`

Prochaine étape : `/project:decompose-tasks 000` (tâches en heures), puis `/sprint:dev US-006`.

---

## Backlog Priorisé

### EPIC-000 — Socle & Walking Skeleton (57 pts)

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
