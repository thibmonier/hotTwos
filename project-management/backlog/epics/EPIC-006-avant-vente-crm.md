# EPIC-006 : Avant-Vente & CRM (Module CRM)

## Métadonnées
- **ID**: EPIC-006
- **Statut**: 🔴 To Do
- **Priorité**: Should Have (MoSCoW)
- **Module**: CRM
- **Lot**: 3
- **Exigences fonctionnelles**: 26 EF
- **MMF**: À affiner — lot 3 (26 EF). Le commerce sait si l'agence peut s'engager sur une date en consultant la capacité disponible consolidée avec le pipeline pondéré, sans remettre de tableur.
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

---

## Description

Module de gestion du pipeline commercial : opportunités, devis, probabilités de signature, jalons avant-vente, et lien structurel vers les projets une fois signés (`INV-8`). Ce module est la source de la charge probable qui alimente EPIC-004 (Planification).

Pain point n°2 (staffing réactif) : sans pipeline visible, le staffing est contraint à l'horizon du projet en cours. Ce module lève ce verrou en injectant la charge probable pondérée dans la planification.

Exclusions structurantes (`ARB-1`) : pas de signature électronique intégrée (tiers — `EF-CRM-19`), pas d'emailing marketing.

---

## Objectifs Business

- `OBJ-4` — Occupation facturable +5 pts : staffing proactif grâce au pipeline pondéré.
- `OBJ-5` — Anticiper le recrutement : pipeline à 6 mois déclencheur des besoins.
- `INV-5` — Charge probable issue du pipeline alimente le modèle PLN.
- `INV-8` — Devis → projet : lien permanent et traçable.

---

## User Stories

À affiner — lot 3 (26 EF)

| ID | Nom | Statut | Points | Sprint |
|----|-----|--------|--------|--------|
| - | À décomposer lors du Sprint Planning lot 3 | - | - | - |

---

## Critères de Succès

### Critères bloquants
- [ ] `INV-8` — Lien permanent devis → projet créé à la signature, non rupture de traçabilité.
- [ ] `INV-5` — La charge probable issue du CRM est distincte de la charge ferme dans le modèle PLN.

### Critères fonctionnels
- [ ] Le commercial sait si la capacité disponible permet de répondre à une opportunité, depuis l'écran devis.
- [ ] Pipeline pondéré visible par le resource manager dans EPIC-004.
- [ ] Lien avec signature électronique tiers configurable (`EF-CRM-19` — intégration externe).

### Critères non-fonctionnels
- [ ] `ENF-PERF-1` — Consultation pipeline < 1 s P95.
- [ ] `ENF-MAINT-4` — API CRM documentée et versionnée.

---

## Progression

0/0 US complétées (0 %)

---

## Dépendances

### Prérequis
- EPIC-000 — Socle.
- EPIC-001 — Référentiel clients.
- EPIC-004 — Modèle de capacité disponible (lot 2 validé). **Dépendance de données lots 1→2→3 : non parallélisable.**

### Dépendants
- EPIC-004 (Planification) — Charge probable injectée (rétroaction lot 3 vers lot 2 dans le modèle vivant).
- EPIC-007 (Pilotage) — Taux de transformation, pipeline KPIs.
