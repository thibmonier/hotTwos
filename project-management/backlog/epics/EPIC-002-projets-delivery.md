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

| ID | Nom | Statut | Points | Sprint |
|----|-----|--------|--------|--------|
| US-030 | Créer et structurer un projet (phases, lots, jalons) | 🔴 To Do | 5 | 3 |
| US-031 | Définir et réviser le budget charge et montant | 🔴 To Do | 5 | 3 |
| US-032 | Affecter des collaborateurs à un projet par profil | 🔴 To Do | 3 | 3 |
| US-033 | Saisir l'avancement physique et le RAF par phase | 🔴 To Do | 5 | 4 |
| US-034 | Consulter la consommation valorisée en temps réel | 🔴 To Do | 5 | 4 |
| US-035 | Calculer l'atterrissage projet (charge et montant) | 🔴 To Do | 8 | 4 |
| US-036 | Recevoir une alerte de dérive avant 50 % de consommation | 🔴 To Do | 5 | 4 |
| US-037 | Gérer les avenants et révisions de périmètre | 🔴 To Do | 5 | 5 |
| US-038 | Exporter le tableau de bord projet (synthèse PDF/CSV) | 🔴 To Do | 3 | 5 |

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

0/9 US complétées (0 %)

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
