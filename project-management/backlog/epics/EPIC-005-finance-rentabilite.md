# EPIC-005 : Finance & Rentabilité (Module FIN)

## Métadonnées
- **ID**: EPIC-005
- **Statut**: 🔴 To Do
- **Priorité**: Must Have / Should Have (MoSCoW selon sous-périmètre)
- **Module**: FIN
- **Lot**: 2
- **Exigences fonctionnelles**: 28 EF
- **MMF**: À affiner — lot 2 (28 EF). Vue consolidée marge réelle par projet et par tenant, réconciliable avec la comptabilité via export, avec un écart marge mi-projet vs clôture ≤ 5 pts (`OBJ-6`).
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

---

## Description

Module de suivi de la rentabilité : calcul de la marge réelle projet (produit facturé − charge valorisée), budget prévisionnel vs réalisé, vue consolidée multi-projets / multi-clients, interface avec la comptabilité (export — `EF-FIN-22`, hors périmètre direct), et pilotage des encours de facturation.

La marge n'est fiable que si les temps saisis et valorisés par EPIC-003 sont fiables. Ce module est en position d'aval de toute la chaîne de valeur des données.

---

## Objectifs Business

- `OBJ-6` — Écart marge mi-projet vs clôture ≤ 5 pts.
- `OBJ-3` — Reporting financier automatisé : éliminer la réconciliation manuelle mensuelle.
- `OBJ-2` — Lecture dérive financière en complément de la dérive charge.
- `ARC-6` — Calculs financiers dans un moteur unique testé (`INV-2` historisation des taux).

---

## User Stories

À affiner — lot 2 (28 EF)

| ID | Nom | Statut | Points | Sprint |
|----|-----|--------|--------|--------|
| - | À décomposer lors du Sprint Planning lot 2 | - | - | - |

---

## Critères de Succès

### Critères bloquants
- [ ] `ARC-6` — Aucun calcul financier dupliqué entre backend et frontend ; moteur unique.
- [ ] `INV-2` — Les taux de valorisation sont historisés ; les marges passées ne sont jamais recalculées rétroactivement.
- [ ] Chiffres opposables aux commissaires aux comptes : traçabilité de chaque ligne de marge.

### Critères fonctionnels
- [ ] Marge réelle = produit facturé (ou factiable) − charge valorisée, calculée à la clôture de chaque période.
- [ ] Export vers la comptabilité (`EF-FIN-22`) : format configurable, sans donnée perdus.
- [ ] Vue consolidée multi-projets et multi-clients pour la direction.

### Critères non-fonctionnels
- [ ] `ENF-PERF-3` — Tableau de bord finance < 3 s P95 sur 5 ans d'historique.
- [ ] `HAB-1` — Coûts unitaires visibles uniquement par les rôles finance/direction.
- [ ] `ENF-MAINT-1` — Couverture tests ≥ 80 % sur calculs de marge.

---

## Progression

0/0 US complétées (0 %)

---

## Dépendances

### Prérequis
- EPIC-000 — Socle.
- EPIC-001 — Taux et contrats.
- EPIC-002 — Budgets projets.
- EPIC-003 — Imputations valorisées (lot 1 validé en production).
- EPIC-004 — Charge prévisionnelle.

### Dépendants
- EPIC-007 (Pilotage) — Indicateurs financiers consolidés pour le reporting.
