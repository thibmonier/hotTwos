# EPIC-004 : Planification & Staffing (Module PLN)

## Métadonnées
- **ID**: EPIC-004
- **Statut**: 🔴 To Do
- **Priorité**: Should Have (MoSCoW)
- **Module**: PLN
- **Lot**: 2
- **Exigences fonctionnelles**: 26 EF
- **MMF**: À affiner — lot 2 (26 EF). Le resource manager arbitre les affectations sur la base de la capacité réelle et du pipeline pondéré consolidés, sans tableur.
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

---

## Description

Module de planification prévisionnelle et de gestion des affectations : capacité disponible par profil/collaborateur, charge ferme et charge probable issue du pipeline CRM (`INV-5`), détection des sur/sous-charges, gestion des conflits d'affectation et aide à la décision de staffing.

Pain point n°2 (`research-summary.md`) : aujourd'hui les affectations sont faites à la semaine, sans visibilité sur le pipeline pondéré.

---

## Objectifs Business

- `OBJ-4` — Améliorer l'occupation facturable de +5 pts sans surcharge.
- `OBJ-5` — Anticiper le recrutement : détection du besoin ≤ 15 j (`RSQ-5` lié).
- `OBJ-3` — Réduire le temps de planification : arbitrage outillé vs tableur.
- `INV-5` — Charge ferme ≠ charge probable : distinction structurelle dans le modèle.

---

## User Stories

À affiner — lot 2 (26 EF)

| ID | Nom | Statut | Points | Sprint |
|----|-----|--------|--------|--------|
| - | À décomposer lors du Sprint Planning lot 2 | - | - | - |

---

## Critères de Succès

### Critères bloquants
- [ ] `INV-5` — Charge ferme et charge probable sont deux champs distincts dans le modèle ; jamais confondus.
- [ ] `ENF-PERF-4` — Plan de charge 12 mois / 150 collaborateurs < 2 s P95.

### Critères fonctionnels
- [ ] Capacité disponible calculée sur la base des absences validées et du taux d'occupation cible.
- [ ] Consolidation charge ferme (projets en cours) + charge probable (pipeline pondéré) sur un seul écran.
- [ ] Détection automatique de sur/sous-charge avec suggestion de ré-affectation.
- [ ] Alerte : besoin en recrutement détecté et notifié en ≤ 15 j horizon (`OBJ-5`).

### Critères non-fonctionnels
- [ ] `HAB-1` — Les coûts unitaires ne sont pas visibles dans les vues planification (sauf rôle finance).
- [ ] Interface utilisable sans formation par un resource manager (`ENF-UX-2`).

---

## Progression

0/0 US complétées (0 %)

---

## Dépendances

### Prérequis
- EPIC-000 — Socle.
- EPIC-001 — Profils et capacités nominales.
- EPIC-002 — Projets et budgets charge.
- EPIC-003 — Consommation réelle par collaborateur.
- EPIC-006 (CRM) — Pipeline pondéré (besoin charge probable) — **dépendance de données lot 1→2→3 : non parallélisable**.

### Dépendants
- EPIC-005 (Finance) — Charge prévisionnelle = entrée du budget prévisionnel.
- EPIC-008 (RH) — Détection des besoins de recrutement.
- EPIC-009 (Recrutement) — Besoins planifiés déclenchent les ouvertures de postes.
