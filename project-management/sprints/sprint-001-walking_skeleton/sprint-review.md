# Sprint Review — Sprint 1 (Walking Skeleton)

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-08-31 |
| Animateur | Scrum Master |
| Incrément | branche `feature/sprint-1-walking-skeleton` — PR [#2](https://github.com/thibmonier/hotTwos/pull/2) (CI verte, `MERGEABLE/CLEAN`) |

## Sprint Goal

> « Charpente applicative : multi-tenant isolé (testé), auth/RBAC, modèle analytique reconstructible. »

**Atteint : ✅ OUI** (une réserve sur le runtime worker, voir ci-dessous).

| Critère du Sprint Goal | État | Preuve |
|------------------------|------|--------|
| Tenant isolé, barrière testée par intrusion (`ENF-SEC-4`) | ✅ | `TenantIsolationTest` + RLS `FORCE` prouvée sous rôle non-superutilisateur |
| Utilisateurs authentifiés et habilités (US-002/003) | ✅ | login JSON + `AuthenticatedTenantResolver` ; `Authorizer` (HAB-1 bout-en-bout) |
| CI/CD verte en mode worker (`ARC-50`) | ⚠️ | CI verte (65 tests) ; **runtime FrankenPHP worker réel reporté** (US-006 T-006-02) |
| Modèle analytique reconstructible sans divergence (`ARC-113`) | ✅ | reconstruction idempotente + non-divergence bloquante en CI |

## Stories livrées

| ID | Titre | Points | Démo | Statut |
|----|-------|--------|------|--------|
| US-001 | Fondation multi-tenant et isolation | 8 | ✅ | ✅ Livré |
| US-002 | Authentification et cycle de vie utilisateurs | 5 | ✅ | ✅ Livré |
| US-003 | Rôles et habilitations (RBAC + périmètre) | 8 | ✅ | ✅ Livré (mécanisme) |
| US-005 | Modèle analytique en étoile et non-divergence | 8 | ✅ | ✅ Livré (tranche Sprint 1) |

**Points engagés : 29 · Points livrés : 29 · Taux de complétion : 100 %**

> US-004 (chaîne CI/CD) était rattachée au Sprint 0. US-003 et US-005 sont livrées au niveau **mécanisme** (sondes), le branchement sur les ressources/faits métier réels relevant du Sprint 2 — cadrage documenté dans chaque US.

## Démonstration (scénarios)

L'incrément se démontre par la **suite de tests verte** (`make ci` : 65 tests / 153 assertions) et l'exécution locale ; le déploiement de cet incrément sur la staging suivra le merge de la PR #2.

### US-001/002/003 — Isolation, auth et habilitation
```gherkin
Given deux tenants A et B avec chacun ses utilisateurs et ses données
When je me connecte en tant que "chef de projet" du tenant A (POST /api/login)
Then le tenant est résolu depuis mon compte, et je ne vois jamais les données de B (filtre ORM + RLS)
When j'appelle GET /api/_probe/collaborator-cost
Then je reçois 403 "Permission refusée : view:collaborator_cost" (HAB-1)
When un "resource manager" appelle le même endpoint
Then il reçoit 200 et l'accès est tracé dans le canal d'audit security (HAB-6)
```

### US-005 — Modèle analytique reconstructible et non-divergent
```gherkin
Given un tenant avec des événements "revenue_recognized" dans le flux
When j'exécute app:analytics:rebuild <tenant>
Then le modèle en étoile est reconstruit à l'identique (idempotent), borné à ce tenant
When une écriture directe est tentée dans fact_project_revenue hors canal événementiel
Then elle est rejetée par la base (trigger), et la RLS masque tout accès sans app.current_tenant
And le test de non-divergence détecte tout écart entre le modèle et le recalcul source (build rouge)
```

## Métriques

| Métrique | Valeur | Note |
|----------|--------|------|
| Points engagés | 29 | vélocité cible 20–40 |
| Points livrés | 29 | 100 % |
| Tests | 65 / 153 assertions | tous verts |
| PHPStan | niveau max | 0 erreur |
| Deptrac | 0 violation | frontières Clean Archi tenues |
| CI GitHub | verte | Qualité & tests + secrets |
| Bugs bloquants | 0 | incident staging `curlie` résolu (non-bug serveur) |

## Feedback des parties prenantes

_À collecter en séance :_
1. Le niveau de garantie de sécurité (isolation, habilitation, audit) est-il conforme aux attentes de conformité (HAB-*, ARC-106) ?
2. Le report du **métier réel** (ressources RBAC, faits analytiques) au Sprint 2 est-il accepté, la charpente étant posée ?
3. Priorité Sprint 2 confirmée sur la **première saisie de temps valorisée** (garde-fou anti-effet-tunnel `RSQ-17`) ?

## Ajustements du backlog (report Sprint 2)

| Action | Élément | Description |
|--------|---------|-------------|
| Reporté | US-005 CA-3 | Réconciliation périodique + alerte `analytical_model_divergence` |
| Reporté | US-005 (ARC-114) | Reconstruction en arrière-plan avec swap atomique |
| Reporté | US-001 | RLS runtime par requête (rôle applicatif non-superutilisateur) |
| Reporté | Infra | Migration Doctrine du DDL de durcissement (RLS + trigger) |
| Reporté | US-006 | Runtime FrankenPHP worker réel (T-006-02) |
| À planifier | ARC-106 | Relecture croisée humaine des règles d'habilitation/isolation avant faits métier |

## Prochaines étapes

1. Merger la PR #2 (Walking Skeleton) après feu vert.
2. `/project:decompose-tasks 002` — planifier le Sprint 2 (première saisie de temps valorisée).
3. Démarrer par les actions de rétro : migrations Doctrine + durcissement versionné, RLS runtime bout-en-bout.
