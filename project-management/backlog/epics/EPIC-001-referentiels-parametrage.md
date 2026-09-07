# EPIC-001 : Référentiels & Paramétrage (Module REF)

## Métadonnées
- **ID**: EPIC-001
- **Statut**: 🟡 En cours (socle livré : org, profils/taux, RBAC, clients, seuils ; reste valorisation multi-niveaux, devises, calendrier fériés, compétences, circuits, onboarding)
- **Priorité**: Must Have (MoSCoW)
- **Module**: REF
- **Lot**: 1
- **Exigences fonctionnelles**: 24 EF (EF-REF-1..11, 15, 16, 19, 20, 22, 23, 24, 25, 26, 29, 30, 31, 33)
- **MMF**: Un tenant paramètre son organisation, ses profils/taux historisés, ses calendriers et ses clients, et devient productif en moins de 15 minutes (`EF-REF-29`).
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

---

## Description

Module fondateur qui structure toutes les données de référence nécessaires au fonctionnement des modules métier : organisation hiérarchique, profils, taux de valorisation historisés (`INV-2`), calendriers et jours ouvrés, gestion des clients et contrats, et paramétrage des accès (authentification, RBAC — `EF-REF-30/31`).

Sans ce module stable, aucune saisie de temps, aucun projet ni aucune valorisation n'est calculable. Il est le prérequis fonctionnel direct de EPIC-002 (Projets) et EPIC-003 (Temps).

---

## Objectifs Business

- `OBJ-1` — Fiabiliser la donnée de temps : les taux de valorisation et profils corrects sont la condition d'une marge calculable.
- `OBJ-7` — Adoption ≥ 85 % : paramétrage autonome sans intervention infra (`ENF-SAAS-2`, `EF-REF-29`).
- `INV-2` — Historisation à date d'effet de tout taux financier : indispensable dès la création des profils.
- `ARC-6` — Calculs de valorisation dans un moteur unique testé ; les taux posés ici en sont l'entrée.

---

## User Stories

> **⚠️ Table réconciliée le 2026-09-06** avec `.bmad/sprint-status.yaml` (source de vérité). La
> numérotation initiale (planifiée 2026-08-31) avait divergé de la livraison réelle. Les capacités du
> module ont été livrées transversalement (socle S1/S4, clients S11, seuils S10).

| ID | Nom | Statut | Sprint | EF-REF |
|----|-----|--------|--------|--------|
| US-010 | Structure organisationnelle du tenant | ✅ Done | 4 | EF-REF-1/2 |
| US-011 | Profils collaborateurs & taux historisés | ✅ Done | 4 | EF-REF-4/5, 20 |
| US-002 | Authentification & cycle de vie utilisateurs | ✅ Done | 1 | EF-REF-30 |
| US-003 | Rôles & habilitations (RBAC + périmètres) | ✅ Done | 1 | EF-REF-31 |
| US-014 | Comptes clients (tranche minimale) | ✅ Done | 11 | EF-REF-15 (16 contacts : partiel) |
| US-018 | Seuils d'alerte paramétrables (tenant) | ✅ Done | 10 | EF-REF-26 (dérive marge) |
| US-015 | Taux de vente multi-niveaux (profil/client/projet) + priorité | ✅ Done | 14 | EF-REF-19 |
| US-016 | Devises & devise de référence tenant | ✅ Done | 14 | EF-REF-22 |
| US-012 | Jours fériés & calcul unifié des jours ouvrés | ✅ Done | 16 | EF-REF-6 |
| US-013 | Référentiel de compétences & niveaux | ✅ Done | 16 | EF-REF-10/11 |
| US-017 | Statuts & circuits de validation paramétrables | 🔵 Backlog | 17 | EF-REF-24/25 |
| US-021 | Calendriers de travail différenciés | 🔵 Backlog | 17 | EF-REF-7 |
| US-022 | Périodes de fermeture entreprise | 🔵 Backlog | 17 | EF-REF-9 |
| US-019 | Onboarding tenant (défauts + checklist) | ✅ Done | 16 | EF-REF-29 |
| US-020 | Journal d'audit du paramétrage | ✅ Done | 16 | EF-REF-33 |

## Couverture des exigences (analyse d'écart — 2026-09-06)

| EF-REF | Prio | État | Détail |
|--------|------|------|--------|
| EF-REF-1/2 (org + rattachement historisé) | M | ✅ | US-010 (S4) |
| EF-REF-4/5 (profils + taux historisés) | M | ✅ | US-011 / `App\Domain\Pricing` (S4) |
| EF-REF-20 (coût de revient + mode) | M | ✅ | `CalculationMode` + `LoadedCostCalculator` |
| EF-REF-8 (types d'absence + impact capacité) | M | ✅ | `AbsenceType` (S5) |
| EF-REF-15 (comptes clients) | M | 🟡 | US-014 (S11) minimal ; hiérarchie groupe/filiale + contacts EF-REF-16 : partiel |
| EF-REF-23 (exercices/clôture) | M | ✅ | `AccountingPeriod` + clôture (S5/S10) |
| EF-REF-26 (seuils d'alerte) | M | 🟡 | US-018 (dérive marge) ; autres seuils : partiel |
| EF-REF-30/31 (users + RBAC périmètres) | M | ✅ | US-002/003 |
| EF-REF-19 (taux vente profil/client/projet + priorité) | M | ✅ | US-015 (S14) — `SellingRate` + `SellingRateResolver` |
| EF-REF-22 (devises + devise de référence) | M | ✅ | US-016 (S14) — `ReferenceCurrency` + `ExchangeRate` + `CurrencyConverter` |
| EF-REF-6 (calendrier tenant + fériés) | M | ✅ | Jours fériés + calcul unifié (US-012, S16). EF-REF-7 différenciés reporté. |
| EF-REF-10/11 (compétences + niveaux) | M | ✅ | Référentiel + échelle (US-013, S16). |
| EF-REF-24/25 (statuts & circuits paramétrables) | M | ❌ | manquant (statuts en enum) → US-017 |
| EF-REF-29 (onboarding < 15 min) | M | ✅ | tenant:init + checklist (US-019, S16). SLA analytics reporté. |
| EF-REF-33 (audit paramétrage) | S | ✅ | Journal append-only + vue gated (US-020, S16). |

**Tranche S14 (valorisation)** : US-015 (taux multi-niveaux, EF-REF-19) + US-016 (devises, EF-REF-22).

---

## Critères de Succès

### Critères bloquants
- [ ] `EF-REF-29` — Un tenant devient productif (org, profils, taux, calendrier, clients) en < 15 min sans intervention infra.
- [ ] `INV-2` — Toute modification de taux est historisée à date d'effet ; les valorisations passées ne changent pas.
- [ ] `EF-REF-30/31` — RBAC fonctionnel : un collaborateur ne voit que son périmètre ; les coûts restent masqués aux chefs de projet (`HAB-1`).

### Critères fonctionnels
- [ ] Modification d'un profil ou d'un taux : effet immédiat pour les saisies futures, sans altération des valorisations validées.
- [ ] Calendrier : jours ouvrés calculés correctement (jours fériés, temps partiels) pour la valorisation.
- [ ] Référentiel clients : lien permanent vers les projets et les contrats (`INV-8`).
- [ ] Import/export des référentiels en autonomie (`ENF-RGPD-9`).

### Critères non-fonctionnels
- [ ] `ENF-PERF-1` — Consultation courante < 1 s P95.
- [ ] `ENF-SEC-5` — Habilitations vérifiées au niveau accès données, pas uniquement à l'affichage.
- [ ] `ENF-MAINT-1` — Couverture tests ≥ 80 % sur règles de valorisation et habilitations.

---

## Progression

Socle livré (org, profils/taux, RBAC, users, clients minimal, seuils, périodes). **MMF quasi atteinte**
(reste calendrier fériés EF-REF-6 et onboarding EF-REF-29). Tranche S14 : US-015 + US-016 (valorisation).
Réserve : US-012 (calendrier), US-013 (compétences), US-017 (statuts/circuits), US-019 (onboarding), US-020 (audit).

---

## Dépendances

### Prérequis
- EPIC-000 — Socle multi-tenant, CI/CD, authentification de base opérationnels.

### Dépendants
- EPIC-002 (Projets) — Clients, contrats, profils nécessaires.
- EPIC-003 (Temps) — Profils, taux, calendriers nécessaires à la valorisation.
- EPIC-004 (Planification) — Profils et capacités en entrée.
- EPIC-005 (Finance) — Taux et contrats en entrée.
