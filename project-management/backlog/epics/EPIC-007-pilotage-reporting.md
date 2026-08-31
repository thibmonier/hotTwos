# EPIC-007 : Pilotage & Reporting (Module PIL)

## Métadonnées
- **ID**: EPIC-007
- **Statut**: 🔴 To Do
- **Priorité**: Should Have (MoSCoW)
- **Module**: PIL
- **Lot**: 3
- **Exigences fonctionnelles**: 22 EF
- **MMF**: À affiner — lot 3 (22 EF). La direction consulte un tableau de bord consolidé (marge, capacité, pipeline, dérive) en < 3 s, sans manipulation ni export manuel.
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

---

## Description

Module de pilotage transversal et de reporting consolidé : tableaux de bord direction (marge, taux d'occupation, pipeline, alertes dérive), vues analytiques multi-axes (client, projet, profil, période), export et partage des rapports.

Ce module est le consommateur final de toutes les données produites par les lots 1 et 2. Sa valeur dépend directement de la qualité de la donnée temps (EPIC-003) et de la complétude du paramétrage (EPIC-001).

---

## Objectifs Business

- `OBJ-3` — Réduire le temps hebdo CP au reporting de 40 %.
- `OBJ-6` — Prévision fiable lisible dans le tableau de bord sans recalcul.
- `OBJ-2` — Les alertes de dérive remontées ici sont celles détectées par EPIC-002.
- `ENF-PERF-3` — Tableaux de bord < 3 s P95 sur 5 ans d'historique.

---

## User Stories

À affiner — lot 3 (22 EF)

| ID | Nom | Statut | Points | Sprint |
|----|-----|--------|--------|--------|
| - | À décomposer lors du Sprint Planning lot 3 | - | - | - |

---

## Critères de Succès

### Critères bloquants
- [ ] `ENF-PERF-3` — Tous les tableaux de bord < 3 s P95 sur jeu de données 5 ans / tenant grand (150 collaborateurs).
- [ ] Chiffres opposables aux commissaires aux comptes (`research-summary.md` § 7 — critère direction).

### Critères fonctionnels
- [ ] Vue direction : marge, taux d'occupation, pipeline pondéré, alertes dérive — sur un seul écran.
- [ ] Filtres multi-axes : client, projet, profil, période, entité.
- [ ] Export CSV/PDF des rapports en autonomie (`ENF-RGPD-9`).

### Critères non-fonctionnels
- [ ] `ARC-111..114` — Modèle analytique alimenté par projection uniquement ; reconstructible.
- [ ] `ENF-MAINT-5` — Jeux de test représentatifs 3 tailles de tenant disponibles pour valider les perfs.

---

## Progression

0/0 US complétées (0 %)

---

## Dépendances

### Prérequis
- EPIC-000 — Socle et modèle analytique en étoile.
- EPIC-001, 002, 003 — Données lot 1 en production.
- EPIC-004, 005 — Données lot 2 disponibles. **Dépendance de données stricte lots 1→2→3.**
- EPIC-006 — Pipeline CRM pour les KPIs avant-vente.

### Dépendants
- Aucun EPIC ne dépend fonctionnellement de PIL (consommateur terminal).
