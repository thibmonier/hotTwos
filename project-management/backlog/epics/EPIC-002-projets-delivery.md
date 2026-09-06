# EPIC-002 : Projets & Delivery (Module PRJ)

## Métadonnées
- **ID**: EPIC-002
- **Statut**: 🟡 En cours (MMF livrée ; reste avenants EF-PRJ-8, projets internes EF-PRJ-5, raffinements)
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
| US-033 | Budget — initial, avenants & budget courant (EF-PRJ-8) | 🟢 Ready | 8 | — |
| US-078 | Budget charge par profil (EF-PRJ-9) | 🟢 Ready | 8 | — |
| US-032 | Projets internes non facturables (EF-PRJ-5) | 🟢 Ready | 5 | — |
| US-079 | Raffinements pilotage — export/courbe/seuil par type (EF-PRJ-14/15/16) | 🟢 Ready | 8 | — |

## Couverture des exigences (analyse d'écart — 2026-09-06)

Mapping des 17 EF déclarées (EF-PRJ-1..5, 8..16, 19, 20, 22) à l'implémentation réelle.

| EF | Prio | État | Détail |
|----|------|------|--------|
| EF-PRJ-1/2/3/4 | M | ✅ | Création, lots 2 niveaux, jalons, cycle de vie (US-030/031). |
| EF-PRJ-10/11 | M/S | ✅ | Engagements externes (US-034), réallocation tracée. |
| EF-PRJ-12/13 | M | ✅ | Avancement physique & RAF par lot (US-035). |
| EF-PRJ-19/20/22 | M | ✅ | Affectation, restriction d'imputation, clôture (US-037/038). |
| EF-PRJ-14 | M | 🟡 | Atterrissage livré (US-036) ; **manque** : « atterrissage = consommé + RAF » (CDC) + **export** des 5 valeurs. |
| EF-PRJ-15 | M | 🟡 | Alerte de dérive précoce livrée (US-036, OBJ-2) ; **manque** : seuil paramétrable **par type de projet** + 2e seuil direction. |
| **EF-PRJ-9** | **M** | 🟡 | Budget charge (jours) + montant globaux ; **manque** la ventilation **par profil** liée aux taux. |
| **EF-PRJ-8** | **M** | ❌ | **Avenants** : budget initial / avenants / budget courant + historique daté (RG-PRJ-4). Bloque INV-8. → US-033. |
| **EF-PRJ-5** | **S** | ❌ | **Projets internes non facturables** (exclusion marge, inclusion capacité, RG-PRJ-6). → US-032. |
| **EF-PRJ-16** | **S** | ❌ | **Courbe d'atterrissage** : historisation + évolution dans le temps. |

**MMF atteinte** (budget lots + avancement/RAF + atterrissage/dérive précoce). Reste à compléter,
désormais **affiné en stories Ready** (2026-09-06) :
- **US-033** — Budget initial / avenants / budget courant (EF-PRJ-8, **Must**) — débloque INV-8.
- **US-078** — Budget charge par profil (EF-PRJ-9, **Must**).
- **US-032** — Projets internes non facturables (EF-PRJ-5, Should, RG-PRJ-6).
- **US-079** — Raffinements pilotage : export (EF-PRJ-14) + courbe (EF-PRJ-16) + seuil par type (EF-PRJ-15), Should — à découper.

Total reste ≈ 29 pts (2 Must + 2 Should) → à séquencer sur 1-2 sprints.

> La dérive **côté marge/montant** est déjà couverte par EPIC-005 (US-072 budget/dérive, US-018 seuil tenant).

---

## Critères de Succès

### Critères bloquants
- [x] `EF-PRJ-14/15` — Atterrissage calculé ; alerte dérive > 10 % déclenchée avant 50 % de consommation (US-036).
- [x] `INV-4` — Avancement, RAF et consommation sont trois champs distincts, jamais déduits l'un de l'autre (US-035/036).
- [ ] `INV-8` — Lien permanent projet → devis + avenants traçable. **(avenants EF-PRJ-8 manquants ; devis relève d'EPIC-006 CRM)**

### Critères fonctionnels
- [x] Un chef de projet consulte l'état complet d'un projet sans ressaisie depuis la saisie de temps validée (fiche projet, onglets).
- [ ] Un avenant modifie le budget sans altérer les imputations historiques (`INV-2`/`INV-3`). **(EF-PRJ-8 manquant)**
- [ ] Détection de dérive testée sur des jeux de données représentatifs (3 tailles de tenant, `ENF-MAINT-5`). **(tests unitaires OK ; 3 tailles non couvertes)**

### Critères non-fonctionnels
- [ ] `ENF-PERF-3` — Tableau de bord projet < 3 s P95 sur 5 ans d'historique. **(non mesuré)**
- [x] `ENF-PERF-5` — Répercussion saisie validée → indicateurs ≤ 15 min (projection async worker).
- [x] `HAB-1` — Le coût unitaire d'un collaborateur n'est jamais visible d'un chef de projet ; seule la marge globale est accessible (US-036 gating).

---

## Progression

7/9 US livrées (78 %) — US-030/031/034/037/038 (S6), US-035/036 (S12). **MMF atteinte.**
Reste : US-033 (avenants EF-PRJ-8 + charge par profil EF-PRJ-9, **Must**), US-032 (projets internes EF-PRJ-5, S),
+ raffinements EF-PRJ-14/15/16 (export, courbe, seuil par type).

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
