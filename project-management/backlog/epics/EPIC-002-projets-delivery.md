# EPIC-002 : Projets & Delivery (Module PRJ)

## Métadonnées
- **ID**: EPIC-002
- **Statut**: 🔴 To Do
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
| US-035 | Avancement physique & RAF par lot | 🟢 Ready | 8 | 12 |
| US-036 | Atterrissage charge & alerte de dérive précoce (< 50 % conso) | 🟢 Ready | 8 | 12 |
| US-033 | Budget charge & montant | 🔵 Backlog | 5 | — |
| US-032 | Projets internes | 🔵 Backlog | 3 | — |

> **Gap restant EPIC-002 (S12)** : le pilotage **côté charge/delivery** — avancement physique, RAF,
> atterrissage charge et détection de dérive avant 50 % de consommation (EF-PRJ-14/15, OBJ-2). La dérive
> **côté marge/montant** est déjà couverte par EPIC-005 (US-072 budget/dérive, US-018 seuil tenant).

---

## Critères de Succès

### Critères bloquants
- [ ] `EF-PRJ-14/15` — Atterrissage calculé en continu ; alerte dérive > 10 % déclenchée avant 50 % de consommation.
- [ ] `INV-4` — Avancement, RAF et consommation sont trois champs distincts, jamais déduits l'un de l'autre.
- [ ] `INV-8` — Lien permanent projet → devis + avenants traçable.

### Critères fonctionnels
- [ ] Un chef de projet consulte l'état complet d'un projet sans ressaisie depuis la saisie de temps validée.
- [ ] Un avenant modifie le budget sans altérer les imputations historiques (`INV-2`/`INV-3`).
- [ ] Détection de dérive testée sur des jeux de données représentatifs (3 tailles de tenant, `ENF-MAINT-5`).

### Critères non-fonctionnels
- [ ] `ENF-PERF-3` — Tableau de bord projet < 3 s P95 sur 5 ans d'historique.
- [ ] `ENF-PERF-5` — Répercussion saisie validée → indicateurs ≤ 15 min.
- [ ] `HAB-1` — Le coût unitaire d'un collaborateur n'est jamais visible d'un chef de projet ; seule la marge globale est accessible.

---

## Progression

5/9 US livrées (56 %) — reste US-035/US-036 (S12, pilotage charge) puis US-033/US-032 (backlog).

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
