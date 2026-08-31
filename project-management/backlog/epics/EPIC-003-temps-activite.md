# EPIC-003 : Temps & Activité (Module TMP)

## Métadonnées
- **ID**: EPIC-003
- **Statut**: 🔴 To Do
- **Priorité**: Must Have (MoSCoW)
- **Module**: TMP
- **Lot**: 1
- **Exigences fonctionnelles**: 19 EF (EF-TMP-1..6, 9, 11, 14..16, 20..24, 26, 27, 29)
- **MMF**: Saisie ≤ 2 min/semaine (`EF-TMP-3`, bloquant), validation, clôture et valorisation automatique ≤ 15 min après validation (`EF-TMP-29`).
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

---

## Description

Module central et point de défaillance unique du dispositif : **sans temps fiable, ni marge ni capacité ne sont calculables.** L'enjeu est d'abord d'adoption, ensuite fonctionnel (`research-summary.md` pain point n°5).

Ce module couvre la saisie hebdomadaire (grille pré-remplie par les affectations, avec pré-remplissage IA en option — `EF-TMP-9`), le circuit de validation managériale, la clôture périodique et la valorisation automatique des imputations (`INV-3` : unité immuable une fois validée).

Le pré-remplissage IA (`EF-TMP-9`, `US-053`) est la première brique de **EPIC-010** (Socle IA mutualisé) ; il doit respecter `ARC-5` (couche d'abstraction unique) et `ENF-RGPD-5` (AIPD signaux d'activité — prérequis bloquant avant activation).

---

## Objectifs Business

- `OBJ-1` — Saisie complète à J+2 ≥ 90 % ; l'UX ≤ 2 min est la condition nécessaire.
- `OBJ-7` — Adoption ≥ 85 % : si la saisie est ressentie comme du flicage sans contrepartie, l'ensemble de la donnée est compromise.
- `OBJ-3` — Réduction du temps de reporting : valorisation automatique ≤ 15 min après validation.
- `OBJ-6` — Marge fiable : valorisation figée à la validation (`INV-3`) empêche les recalculs rétroactifs.
- `ENF-UX-1` — Critère bloquant du lot 1 : test utilisateur sur 5 profils, saisie ≤ 2 min validée.

---

## User Stories

| ID | Nom | Statut | Points | Sprint |
|----|-----|--------|--------|--------|
| US-050 | Afficher la grille de saisie hebdomadaire pré-remplie | 🔴 To Do | 8 | 3 |
| US-051 | Saisir et enregistrer ses heures en moins de 2 minutes | 🔴 To Do | 5 | 3 |
| US-052 | Saisir des activités hors projet (congés, formation, inter-contrat) | 🔴 To Do | 3 | 3 |
| US-053 | Pré-remplir automatiquement la grille par IA (signaux d'activité) | 🔴 To Do | 8 | 5 |
| US-054 | Soumettre sa saisie pour validation managériale | 🔴 To Do | 3 | 3 |
| US-055 | Valider ou rejeter les saisies de son équipe | 🔴 To Do | 5 | 4 |
| US-056 | Suivre l'avancement de la saisie de l'équipe (taux de complétion) | 🔴 To Do | 3 | 4 |
| US-057 | Clôturer la période et figer les imputations validées | 🔴 To Do | 5 | 4 |
| US-058 | Valoriser automatiquement les imputations après clôture | 🔴 To Do | 8 | 4 |
| US-059 | Corriger une saisie validée (procédure tracée et justifiée) | 🔴 To Do | 5 | 5 |
| US-060 | Consulter l'historique de ses saisies et valorisations | 🔴 To Do | 3 | 5 |

---

## Critères de Succès

### Critères bloquants
- [ ] `ENF-UX-1` — Test utilisateur sur 5 profils : saisie complète ≤ 2 min/semaine. **BLOQUANT LOT 1.**
- [ ] `EF-TMP-29` — Valorisation ≤ 15 min après validation de la période.
- [ ] `INV-3` — Imputation validée = unité immuable : aucune modification silencieuse des valorisations passées.
- [ ] `ENF-RGPD-5` — AIPD réalisée avant activation du pré-remplissage IA (`EF-TMP-9`). **PRÉREQUIS BLOQUANT.**

### Critères fonctionnels
- [ ] Grille pré-remplie avec les affectations projet de la semaine dès l'ouverture.
- [ ] Activités hors projet (congés, formation, inter-contrat) saisissables sur la même grille.
- [ ] Circuit de validation managériale : soumission → validation / rejet → notification.
- [ ] Clôture de période : les imputations non validées sont signalées ; la clôture est irréversible.
- [ ] Correction post-clôture : procédure tracée, justification obligatoire, piste d'audit (`INV-7`).

### Critères non-fonctionnels
- [ ] `ENF-PERF-2` — Saisie de temps < 500 ms P95.
- [ ] `ENF-UX-3` — Interface de saisie utilisable sur mobile.
- [ ] `ENF-MAINT-1` — Couverture tests ≥ 80 % sur valorisation et règles de clôture.
- [ ] `ARC-5` — Pré-remplissage IA (`US-053`) passe par la couche d'abstraction IA de EPIC-010, jamais appel direct.
- [ ] `ENF-SEC-6` — Le contexte IA ne contient que les données auxquelles l'utilisateur a accès (`HAB-5`).

---

## Progression

0/11 US complétées (0 %)

---

## Dépendances

### Prérequis
- EPIC-000 — Socle.
- EPIC-001 — Profils, taux et calendriers disponibles.
- EPIC-002 — Projets et affectations disponibles pour le pré-remplissage de la grille.
- EPIC-010 (brique 1 IA) — Pour `US-053` (pré-remplissage IA) uniquement ; la saisie manuelle fonctionne sans.
- `ENF-RGPD-5` — AIPD avant activation `EF-TMP-9`.

### Dépendants
- EPIC-004 (Planification) — La consommation réelle valorisée alimente le plan de charge réel.
- EPIC-005 (Finance) — Valorisation des imputations = entrée principale du calcul de rentabilité.
- EPIC-007 (Pilotage) — Taux de saisie, valorisation, indicateurs d'activité.
