# Registre de dette « Walking Skeleton »

> Créé en réponse à l'action 4 de la rétrospective Sprint 1. Centralise les éléments
> **volontairement reportés** lors de la pose de la charpente (sondes plutôt que métier
> réel, mécanismes prouvés mais pas encore généralisés). Chaque entrée pointe la US ou le
> sprint où elle sera soldée. Revu à chaque revue de sprint.

## Sécurité / isolation

| Réf | Dette | Posé au | À solder |
|-----|-------|---------|----------|
| DBT-SEC-1 | **RLS runtime limitée au plan de données** (`protected_record` + analytique). `app_user` et `auth_role` restent isolés par le **filtre ORM** seul : `app_user` est interrogé *avant* la résolution du tenant (login), et `auth_role`/l'init de matrice écrivent hors contexte requête. Les rendre RLS suppose des chemins d'écriture *tenant-aware* (CLI, inscription) et une stratégie de login (indice de tenant, l'e-mail n'étant unique que par tenant). | S2 · TECH-2 | Story « auth multi-tenant durcie » (Sprint 3+) |
| DBT-SEC-2 | **Double source du DDL de durcissement** : `AnalyticsSchemaHardener` (tests + commande) et la migration `Version20260831161807` portent le même SQL. | S2 · TECH-1 | Converger quand le DDL évolue (ADR-0017) |
| DBT-SEC-3 | **Relecture croisée humaine ARC-106** des règles d'habilitation/isolation, à planifier avant tout branchement de faits/ressources métier sensibles. | S1 · US-003 | Avant 1re US métier sensible |

## Analytique (US-005)

| Réf | Dette | Posé au | À solder |
|-----|-------|---------|----------|
| DBT-ANA-1 | **Réconciliation périodique en production** + alerte `analytical_model_divergence` (Slack/PagerDuty) — CA-3. | S1 · US-005 | Sprint 3 |
| DBT-ANA-2 | **Reconstruction atomique** en arrière-plan avec swap des tables (ARC-114 complet). | S1 · US-005 | Sprint 3 |
| DBT-ANA-3 | **Faits métier réels** (`fact_timesheet`, `dim_collaborator`, `dim_project`) branchés sur les événements métier, en remplacement de la sonde `RevenueRecognized`. | S1 · US-005 | Sprints métier |

## RBAC (US-003)

| Réf | Dette | Posé au | À solder |
|-----|-------|---------|----------|
| DBT-RBAC-1 | **Branchement sur ressources métier réelles** (`/api/collaborators/{id}/cost`, `/api/projects/{id}`) et **filtrage effectif des instances par périmètre** (« ses projets », « son pôle »), qui suppose les entités métier. Le mécanisme (`Authorizer`) est prêt et réutilisable. | S1 · US-003 | Sprints métier |

## Runtime / socle

| Réf | Dette | Posé au | À solder |
|-----|-------|---------|----------|
| DBT-RUN-1 | **Runtime FrankenPHP worker réel** + preuve d'absence de fuite d'état inter-requêtes. | S0 · US-006 | S2 · US-006 (en cours) |
| DBT-RUN-2 | **DATABASE_URL de production via `hotones_app`** pour activer la RLS runtime. **Préparé (TECH-3)** : `hotones_app` a LOGIN (migration `Version20260831183000`), l'entrypoint sépare l'URL migrations (privilégié) de l'URL app (`MIGRATION_DATABASE_URL`), procédure de bascule + rollback dans le runbook. **Reste** : poser le mot de passe du rôle + basculer `DATABASE_URL` sur Railway (action ops), puis vérifier. | S2 · TECH-2 → S3 · TECH-3 | Bascule Railway (ops) |
| DBT-SEC-1 (suite) | **Étendre la RLS aux tables métier** `project`/`time_entry`/`auth_role` (aujourd'hui : ORM filter seul). À tester sous `hotones_app` sur la staging avant activation (écritures en contexte requête → WITH CHECK). | S3 | Après bascule DBT-RUN-2 |
