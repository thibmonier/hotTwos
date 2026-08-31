# Sprint Review — Sprint 2 (Consolidation technique)

## Informations

| Attribut | Valeur |
|----------|--------|
| Date | 2026-08-31 |
| Animateur | Scrum Master |
| Incrément | branche `feature/sprint-2-consolidation` (11 commits), CI verte |
| Staging | https://hottwos-production.up.railway.app — **mode worker**, validé en direct |

## Sprint Goal

> « Le socle technique est durci et reproductible : le schéma est versionné par migrations, l'isolation RLS est active au runtime, l'exécution worker est éprouvée et l'observabilité est opérationnelle. »

**Atteint : ✅ OUI** (une réserve documentée sur l'activation RLS en production).

| Objectif de sortie | État | Preuve |
|--------------------|------|--------|
| Schéma versionné par migrations (plus de SchemaTool en prod) | ✅ | 4 migrations, `schema:validate` in sync, étape CI dédiée, appliquées au démarrage du conteneur |
| RLS active au runtime | ⚠️ | Mécanisme livré et prouvé sous rôle non-superutilisateur (test) ; **inactif en prod** tant que `DATABASE_URL` n'utilise pas `hotones_app` (DBT-RUN-2) |
| Worker éprouvé (pas de fuite d'état) | ✅ | `kernel.reset` sur `RequestTenantContext`, test RSQ-15 ; **validé en prod** (worker stable, requêtes en série OK) |
| Observabilité opérationnelle | ✅ | `/metrics` Prometheus **live en prod**, Sentry UE (DSN Railway) ; secrets rotatifs via variables Railway |

## Stories livrées

| ID | Titre | Points | Démo | Statut |
|----|-------|--------|------|--------|
| TECH-1 | Migrations Doctrine + durcissement versionné | 5 | ✅ | ✅ Livré |
| TECH-2 | RLS runtime par requête (rôle applicatif) | 5 | ✅ | ✅ Livré (mécanisme) |
| US-006 | Worker FrankenPHP réel + état inter-requêtes sûr | 5 | ✅ | ✅ Livré (validé prod) |
| US-008 | Secrets rotatifs + observabilité (P95, Sentry) | 5 | ✅ | ✅ Livré |

**Points engagés : 20 · Points livrés : 20 · Taux : 100 %**

## Démonstration (validée en direct sur staging)

```gherkin
Given le service tourne en mode worker FrankenPHP sur Railway
When j'appelle GET /health, /api/status, /metrics
Then chacun répond 200 (worker stable sur requêtes consécutives)
When j'appelle GET /metrics après du trafic
Then l'histogramme Prometheus reflète les latences (http_requests_total croît → le singleton persiste entre requêtes worker ; P95 < 0,5 s)
When je poste des identifiants invalides sur /api/login
Then la réponse est 401 (schéma migré au démarrage : app_user existe, auth opérationnelle)
```

## Métriques

| Métrique | Valeur |
|----------|--------|
| Points livrés | 20 / 20 (100 %) |
| Tests | 73 / 177 assertions (tous verts) |
| Migrations | 4 (schéma + durcissement), `schema:validate` in sync |
| PHPStan / Deptrac | niveau max / 0 violation |
| Déprécations | 0 (correction `#[Autowire]` canal security — ARC-51) |
| Latence P95 staging | < 500 ms (cible ENF-PERF-2 tenue en worker) |

## Découvertes de la validation déploiement

- **Le paquet `runtime/frankenphp-symfony` ne supporte pas Symfony 8** → bridge worker manuel écrit (compatible Symfony 8).
- **`preDeployCommand` (railway.toml) non honoré** par `railway up` (config-as-code dépréciée) → migrations déplacées dans l'entrypoint conteneur (`docker/start.sh`), plus robuste.
- **Base Railway interne** (`postgres.railway.internal`) injoignable en local → migrations exécutées dans le conteneur au démarrage.
- Une **déprécation Symfony 8.1** (autowiring par nom) révélée en déploiement, corrigée.

## Feedback des parties prenantes

_À collecter :_
1. La réserve « RLS inactive en prod jusqu'à bascule sur `hotones_app` » est-elle acceptable pour le staging actuel (isolation portée par le filtre ORM) ?
2. Priorité confirmée : **Sprint 3 = première saisie de temps valorisée** ?

## Ajustements du backlog (Sprint 3)

| Action | Élément | Description |
|--------|---------|-------------|
| Reporté | DBT-RUN-2 | Basculer `DATABASE_URL` prod sur `hotones_app` pour activer la RLS runtime |
| Reporté | DBT-ANA-1/2/3 | Réconciliation périodique, swap atomique, faits métier réels |
| Nouveau | Métier | US-050/051/055/060 (saisie → validation → valorisation) à décomposer |

## Prochaines étapes

1. Rétrospective Sprint 2.
2. Ouvrir la PR `feature/sprint-2-consolidation → main`.
3. `/project:decompose-tasks 003` — Sprint 3 (valeur métier).
