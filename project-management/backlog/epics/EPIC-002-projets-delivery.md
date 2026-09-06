# EPIC-002 : Projets & Delivery (Module PRJ)

## Métadonnées
- **ID**: EPIC-002
- **Statut**: 🟢 Quasi terminé (toutes capacités Must+Should livrées ; reste raffinements US-079)
- **Priorité**: Must Have (MoSCoW)
- **Module**: PRJ
- **Lot**: 1
- **Exigences fonctionnelles**: 17 EF (EF-PRJ-1..5, 8..16, 19, 20, 22)
- **MMF**: Suivi budget charge + montant, avancement physique et RAF, atterrissage et détection automatique de dérive avant 50 % de consommation (`EF-PRJ-14/15`).
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

---

## Description

Module de pilotage des projets en cours : création et structuration des projets (phases, lots), affectation des budgets (charge et montant), suivi de l'avancement physique déclaré, du reste-à-faire (RAF) et de la consommation réelle valorisée (`INV-4`). Calcul d'atterrissage en continu et déclenchement d'alertes de dérive dès que la trajectoire dépasse 10 % avant 50 % de consommation (`OBJ-2`).

Pain point n°1 (`research-summary.md`) : la dérive est aujourd'hui détectée quand 60-80 % du budget est consommé. Ce module la rend visible dès 50 %.

---

## Objectifs Business

- `OBJ-2` — Détecter ≥ 75 % des dépassements > 10 % avant 50 % de consommation.
- `OBJ-3` — Réduire le temps hebdo CP au reporting de 40 % (suivi automatisé vs tableur).
- `OBJ-6` — Fiabiliser la prévision : écart marge mi-projet vs clôture ≤ 5 pts.
- `INV-4` — Avancement / RAF / consommation = trois données distinctes, jamais confondues.
- `INV-8` — Lien permanent projet → devis + avenants.

---

## User Stories

> **⚠️ Table réconciliée le 2026-09-06** avec `.bmad/sprint-status.yaml` (source de vérité). La
> numérotation initiale (ci-dessous, planifiée 2026-08-31) avait divergé de la livraison réelle ;
> les titres/statuts/sprints ci-dessous reflètent désormais l'état effectif du dépôt.

| ID | Nom | Statut | Points | Sprint |
|----|-----|--------|--------|--------|
| US-030 | Création projet & statuts | ✅ Done | 5 | 6 |
| US-031 | Lots & jalons | ✅ Done | 5 | 6 |
| US-034 | Engagements externes | ✅ Done | 5 | 6 |
| US-037 | Affectation & restriction d'imputation | ✅ Done | 5 | 6 |
| US-038 | Clôture opérationnelle projet | ✅ Done | 3 | 6 |
| US-035 | Avancement physique & RAF par lot | ✅ Done | 8 | 12 |
| US-036 | Atterrissage charge & alerte de dérive précoce (< 50 % conso) | ✅ Done | 8 | 12 |
| US-033 | Budget — initial, avenants & budget courant (EF-PRJ-8) | ✅ Done | 8 | 13 |
| US-078 | Budget charge par profil (EF-PRJ-9) | ✅ Done | 8 | 13 |
| US-032 | Projets internes non facturables (EF-PRJ-5) | ✅ Done | 5 | 13 |
| US-079 | Raffinements pilotage — export/courbe/seuil par type (EF-PRJ-14/15/16) | 🟢 Ready | 8 | — |

## Couverture des exigences (analyse d'écart — 2026-09-06)

Mapping des 17 EF déclarées (EF-PRJ-1..5, 8..16, 19, 20, 22) à l'implémentation réelle.

| EF | Prio | État | Détail |
|----|------|------|--------|
| EF-PRJ-1/2/3/4 | M | ✅ | Création, lots 2 niveaux, jalons, cycle de vie (US-030/031). |
| EF-PRJ-10/11 | M/S | ✅ | Engagements externes (US-034), réallocation tracée. |
| EF-PRJ-12/13 | M | ✅ | Avancement physique & RAF par lot (US-035). |
| EF-PRJ-19/20/22 | M | ✅ | Affectation, restriction d'imputation, clôture (US-037/038). |
| EF-PRJ-14 | M | ✅ | Atterrissage (US-036) + **export CSV** (US-079a, S14). Reste nuance CDC « atterrissage = consommé + RAF » (mineure). |
| EF-PRJ-15 | M | 🟡 | Alerte de dérive précoce livrée (US-036, OBJ-2) ; **manque** : seuil paramétrable **par type de projet** + 2e seuil direction. |
| EF-PRJ-9 | M | ✅ | Budget charge **par profil** lié aux taux historisés (US-078, S13). |
| EF-PRJ-8 | M | ✅ | **Avenants** : budget initial / courant + historique daté (US-033, S13). Débloque INV-8. |
| EF-PRJ-5 | S | ✅ | **Projets internes non facturables** (exclusion marge, occupation facturable, US-032, S13). |
| **EF-PRJ-16** | **S** | ❌ | **Courbe d'atterrissage** : historisation + évolution — reste (US-079). |

**MMF atteinte + toutes les capacités Must complètes** (S6/S12/S13). Reste uniquement les raffinements
*Should* regroupés dans **US-079** : export des 5 valeurs (EF-PRJ-14), courbe d'atterrissage (EF-PRJ-16),
seuil de dérive par type de projet + 2e seuil direction (EF-PRJ-15) — ~8 pts, à découper.

> La dérive **côté marge/montant** est déjà couverte par EPIC-005 (US-072 budget/dérive, US-018 seuil tenant).

---

## Critères de Succès

### Critères bloquants
- [x] `EF-PRJ-14/15` — Atterrissage calculé ; alerte dérive > 10 % déclenchée avant 50 % de consommation (US-036).
- [x] `INV-4` — Avancement, RAF et consommation sont trois champs distincts, jamais déduits l'un de l'autre (US-035/036).
- [x] `INV-8` — volet **avenants** traçable (US-033) ; le volet **devis** relève d'EPIC-006 (CRM).
- [x] Un avenant modifie le budget sans altérer les imputations historiques (`INV-2`/`INV-3`) — US-033.

### Critères fonctionnels
- [x] Un chef de projet consulte l'état complet d'un projet sans ressaisie depuis la saisie de temps validée (fiche projet, onglets).
- [ ] Détection de dérive testée sur des jeux de données représentatifs (3 tailles de tenant, `ENF-MAINT-5`). **(tests unitaires OK ; 3 tailles non couvertes)**

### Critères non-fonctionnels
- [ ] `ENF-PERF-3` — Tableau de bord projet < 3 s P95 sur 5 ans d'historique. **(non mesuré)**
- [x] `ENF-PERF-5` — Répercussion saisie validée → indicateurs ≤ 15 min (projection async worker).
- [x] `HAB-1` — Le coût unitaire d'un collaborateur n'est jamais visible d'un chef de projet ; seule la marge globale est accessible (US-036 gating).

---

## Progression

10/10 US Must+Should livrées (100 % du périmètre EF déclaré) — S6 (5), S12 (US-035/036), S13 (US-033/078/032).
**MMF + toutes capacités Must complètes.** Reste hors périmètre EF déclaré : US-079 (raffinements Should
export/courbe/seuil, EF-PRJ-14/15/16 — ~8 pts).

---

## Dépendances

### Prérequis
- EPIC-000 — Socle.
- EPIC-001 — Clients, contrats, profils et taux historisés disponibles.

### Dépendants
- EPIC-003 (Temps) — Les saisies de temps s'imputent aux projets définis ici.
- EPIC-004 (Planification) — Les projets alimentent le plan de charge.
- EPIC-005 (Finance) — Les budgets et consommations alimentent la rentabilité.
- EPIC-007 (Pilotage) — Les indicateurs projet alimentent le reporting consolidé.
