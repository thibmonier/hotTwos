# EPIC-001 : Référentiels & Paramétrage (Module REF)

## Métadonnées
- **ID**: EPIC-001
- **Statut**: 🔴 To Do
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

| ID | Nom | Statut | Points | Sprint |
|----|-----|--------|--------|--------|
| US-010 | Créer et configurer la structure organisationnelle du tenant | 🔴 To Do | 5 | 2 |
| US-011 | Gérer les profils collaborateurs et leurs taux historisés | 🔴 To Do | 8 | 2 |
| US-012 | Paramétrer les calendriers et jours ouvrés par entité | 🔴 To Do | 5 | 3 |
| US-013 | Créer et gérer le référentiel clients et donneurs d'ordre | 🔴 To Do | 5 | 3 |
| US-014 | Gérer les types de contrat et les conditions tarifaires | 🔴 To Do | 5 | 3 |
| US-015 | Administrer les habilitations (rôles, droits, périmètres) | 🔴 To Do | 8 | 2 |
| US-016 | Configurer l'authentification et le 2FA par tenant | 🔴 To Do | 5 | 2 |
| US-017 | Gérer les devises, unités et règles de valorisation | 🔴 To Do | 3 | 3 |
| US-018 | Importer / exporter les référentiels (bootstrap tenant) | 🔴 To Do | 5 | 4 |
| US-019 | Consulter l'historique des modifications de paramétrage | 🔴 To Do | 3 | 4 |
| US-020 | Valider la complétude du paramétrage avant mise en production | 🔴 To Do | 3 | 4 |

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

0/11 US complétées (0 %)

---

## Dépendances

### Prérequis
- EPIC-000 — Socle multi-tenant, CI/CD, authentification de base opérationnels.

### Dépendants
- EPIC-002 (Projets) — Clients, contrats, profils nécessaires.
- EPIC-003 (Temps) — Profils, taux, calendriers nécessaires à la valorisation.
- EPIC-004 (Planification) — Profils et capacités en entrée.
- EPIC-005 (Finance) — Taux et contrats en entrée.
