# EPIC-000 : Socle — Walking Skeleton

## Métadonnées
- **ID**: EPIC-000
- **Statut**: 🔴 To Do
- **Priorité**: Must Have (MoSCoW)
- **Lot**: 0 / 1
- **MMF**: Une tranche verticale end-to-end (saisie → validation → valorisation → indicateur) sur socle multi-tenant dont l'isolation est vérifiée par test d'intrusion.
- **Créé le**: 2026-08-31
- **Mis à jour**: 2026-08-31

---

## Description

Pose les fondations non rétro-adaptables de HotOnes : architecture multi-tenant à isolation logique vérifiable (`INV-1`, `ARC-2`), authentification et RBAC (`EF-REF-30/31`), pipeline CI/CD et mode worker sans état (`ADR-12`, `ARC-47/50`), et modèle analytique en étoile reconstructible (`ADR-9`, `ARC-111..114`).

Ce socle est le prérequis bloquant de **tous** les modules métier. Il est conçu pour que l'ajout de chaque module ultérieur soit une greffe sur un terrain stable, jamais une réécriture du cœur.

L'objectif du Walking Skeleton est de démontrer, en conditions réelles sur une organisation pilote, que la chaîne complète fonctionne de bout en bout — même sur un périmètre minimal — avant d'ouvrir le lot 1 fonctionnel.

---

## Objectifs Business

- `OBJ-1` — Poser `INV-1` (tenant sur toute entité) et `INV-2` (historisation à date d'effet) dès le premier schéma, avant toute donnée de production.
- `OBJ-7` — Garantir que le produit est adoptable : une tranche verticale complète utilisable en production est la preuve concrète.
- `CDR-1` — Invariants `INV-1..8` posés et non rediscutés ensuite.
- `CDR-3` — Livrer de la valeur visible en production sur l'organisation pilote dans le délai cible (4-5 mois).
- `ENF-SEC-4` — Isolation inter-tenant vérifiée par test d'intrusion (identifiant forgé, export, IA) — **critère bloquant**.
- `ENF-MAINT-1` — Couverture de tests ≥ 80 % sur les règles critiques dès le socle — **seuil bloquant CI**.

---

## User Stories

**Sprint 0 — Fondations & Outillage (installation, 28 pts)**

| ID | Nom | Statut | Points | Sprint |
|----|-----|--------|--------|--------|
| US-006 | Squelette Symfony 8 + FrankenPHP worker + architecture | 🔴 To Do | 8 | 0 |
| US-007 | Environnement conteneurisé + données de test | 🔴 To Do | 5 | 0 |
| US-004 | Chaîne CI/CD et exécution en mode worker | 🔴 To Do | 5 | 0 |
| US-008 | Staging, secrets et observabilité de base | 🔴 To Do | 5 | 0 |
| US-009 | Outillage qualité/sécurité + conventions agent | 🔴 To Do | 5 | 0 |

**Sprint 1 — Walking Skeleton applicatif (29 pts)**

| ID | Nom | Statut | Points | Sprint |
|----|-----|--------|--------|--------|
| US-001 | Fondation multi-tenant et isolation des données | 🔴 To Do | 8 | 1 |
| US-002 | Authentification et cycle de vie des utilisateurs | 🔴 To Do | 5 | 1 |
| US-003 | Rôles et habilitations (RBAC + périmètre de données) | 🔴 To Do | 8 | 1 |
| US-005 | Modèle analytique en étoile et non-divergence | 🔴 To Do | 8 | 1 |

**Backlog — Identité & authentification (non assigné)**

| ID | Nom | Statut | Points | Sprint |
|----|-----|--------|--------|--------|
| US-067 | Enrichissement du profil utilisateur (nom et prénom) | 🔴 To Do | 3 | — |
| US-068 | Écrans web d'authentification (login, mot de passe oublié, déconnexion, changement de mot de passe) | 🔴 To Do | 8 | — |

> Question ouverte (hors périmètre US-068) : **création/provisionnement d'un compte** — à définir plus tard.

---

## Critères de Succès

### Critères bloquants (aucune MEP sans)
- [ ] `ENF-SEC-4` — Test d'intrusion dédié : isolation inter-tenant validée (identifiant forgé, export, IA).
- [ ] `ENF-MAINT-1` — Couverture tests ≥ 80 % sur valorisation, habilitations, marge.
- [ ] `ARC-63` — Frontières de modules vérifiées automatiquement (Deptrac) en CI.

### Critères fonctionnels
- [ ] Un tenant peut être créé en < 15 min sans intervention infra (`ENF-SAAS-2`).
- [ ] La tranche saisie → validation → valorisation → indicateur fonctionne de bout en bout sur la org pilote.
- [ ] Mode worker : aucun état conservé entre requêtes ; tenant posé/effacé à chaque requête (`ARC-47/50`).
- [ ] Modèle analytique reconstructible et test de non-divergence passant en CI (`ARC-113/114`).
- [ ] `INV-2` (historisation à date d'effet) et `INV-3` (imputation immuable) implémentés dans le schéma initial.

### Critères non-fonctionnels
- [ ] `ENF-PERF-2` — Saisie de temps < 500 ms P95.
- [ ] `ENF-DISPO-1` — Disponibilité ≥ 99,5 % heures ouvrées mesurable dès le pilote.
- [ ] CI/CD avec pipelines dev / recette / prod distincts (`ENF-MAINT-2`).

---

## Progression

0/11 US complétées (0 %) · 68 points (Sprint 0 : 28 · Sprint 1 : 29 · Backlog identité/auth : 11)

---

## Dépendances

### Prérequis
- `AUD-1` — Audit technique de l'existant (couverture, dette, présence multi-tenant) réalisé avant démarrage.
- `AUD-2` — Cartographie fonctionnelle réelle du MVP vs exigences `M`.
- `AUD-3` — Mesure des situations de référence `OBJ-1..7` sur 4 semaines.

### Dépendants (bloqués par EPIC-000)
- Tous les EPICs fonctionnels : EPIC-001, 002, 003, 004, 005, 006, 007, 008, 009.
- EPIC-010 (IA) pour la couche d'abstraction et les clés par tenant.
- EPIC-011 (industrialisation SaaS) pour l'onboarding self-service.

---

## Notes

> `INV-2` et `INV-3` sont les plus fréquemment omis et les plus coûteux à récupérer. À poser dès le premier schéma (`CDR-1`).
> Le socle est multi-tenant dès le lot 1, même en démarrage mono-organisation — se rétro-adapter coûterait très cher (`ARB-20`).
