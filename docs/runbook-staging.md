# Runbook — Staging (Railway)

> US-008 / ADR-13 / ADR-14. Environnement de staging HotOnes : Railway, région UE, **sans données réelles**. Service `hotTwos`, projet `powerful-tranquility`.

## Accès

- URL : https://hottwos-production.up.railway.app
- CLI : `railway status` (service lié), `railway logs` (runtime), `railway logs --deployment` (build/déploiement).

## Déploiement

```bash
railway up            # build (Dockerfile FrankenPHP) + déploiement
railway up --detach   # sans streamer les logs
```

- **Runtime** : FrankenPHP en **mode worker** (ADR-2) — kernel chargé une fois, état inter-requêtes réinitialisé (RSQ-15).
- **Migrations** : appliquées automatiquement au démarrage du conteneur (`docker/start.sh`), avant que le serveur ne serve du trafic. Idempotentes.
- **Healthcheck** : `/health` (Railway, timeout 120 s).
- **Rollback** : redéployer le commit précédent (`git checkout <sha> && railway up`) ou via le dashboard Railway (Deployments → Redeploy).

## Variables d'environnement (hors dépôt — ARC-88)

À définir dans le service Railway (`railway variables --set "CLE=valeur"` ou dashboard) :

| Variable | Rôle | Obligatoire |
|----------|------|-------------|
| `APP_ENV` | `staging` | oui |
| `APP_SECRET` | secret Symfony | **oui** (démarrage refusé si absent) |
| `DATABASE_URL` | PostgreSQL Railway | **oui** (démarrage refusé si absent) |
| `SERVER_NAME` | `:${PORT}` (port fourni par Railway) | oui |
| `SENTRY_DSN` | suivi d'erreurs Sentry (UE) | non (Sentry inactif si vide) |

- **Rotation d'un secret** : mettre à jour la variable dans Railway ; le service redémarre avec la nouvelle valeur, **sans nouveau build de code** (ENF-SEC-10).
- **Variable obligatoire manquante** : le conteneur échoue explicitement au démarrage (`docker/start.sh`), le déploiement ne passe pas (CA-4).

## Observabilité

- **Métriques** : `GET /metrics` (format Prometheus) — `http_requests_total`, histogramme `http_request_duration_seconds` (P95 via `histogram_quantile(0.95, rate(http_request_duration_seconds_bucket[5m]))`). Scrapable par un Prometheus/Grafana.
- **Erreurs** : Sentry (si `SENTRY_DSN` défini) — région UE, `send_default_pii=false` (RGPD). Les erreurs applicatives y remontent ; sinon, `railway logs`.
- **Latence** : cible ENF-PERF-2 < 500 ms P95 (mode worker).

## Données (ADR-13)

- **Aucune donnée réelle** en staging. Jeux de données **synthétiques** uniquement, via `app:fixtures:load`.
- La commande `app:fixtures:load` **refuse de s'exécuter en production sans `--force`** (garde anti-données).
- Ne jamais importer de dump de production dans le staging.

## Bascule RLS en production (TECH-3)

Objectif : faire tourner l'application sous le rôle **non-superutilisateur** `hotones_app`
pour que les politiques RLS (ARC-34) s'appliquent réellement (aujourd'hui inertes car le
rôle Railway par défaut est superutilisateur → RLS contournée).

**Prérequis** : la migration `Version20260831183000` a donné `LOGIN` à `hotones_app` (au
déploiement). Le rôle a déjà les privilèges DML (migration Sprint 2).

**Procédure (variables Railway, hors dépôt — ARC-88) :**

1. **Mot de passe du rôle applicatif** (une fois, via la console PostgreSQL Railway) :
   ```sql
   ALTER ROLE hotones_app PASSWORD '<mot_de_passe_fort>';
   ```
2. **Variables du service** :
   - `MIGRATION_DATABASE_URL` = l'URL **privilégiée actuelle** (rôle par défaut) — les
     migrations continuent via ce rôle (DDL + gestion des rôles).
   - `DATABASE_URL` = même hôte/base mais avec `hotones_app:<mot_de_passe>` — l'**application**
     s'exécute désormais sous ce rôle (RLS active).
3. **Redéployer** (`railway up`). `docker/start.sh` migre via `MIGRATION_DATABASE_URL`, puis
   sert via `DATABASE_URL` (hotones_app).
4. **Vérifier** : `make smoke URL=https://hottwos-production.up.railway.app` (endpoints OK) ;
   un utilisateur authentifié voit ses données (RLS + `app.current_tenant` posé par requête).

**Rollback** : remettre `DATABASE_URL` sur l'URL privilégiée (supprimer/renseigner l'ancienne
valeur) et redéployer. L'application repasse superutilisateur (RLS inerte, filtre ORM actif).

**Périmètre RLS actif** : `protected_record`, `dim_period`, `fact_project_revenue`. L'extension
aux tables métier (`project`, `time_entry`, `auth_role`) est à instruire et à tester sous
`hotones_app` sur la staging avant activation (voir `walking-skeleton-debt.md`, DBT-SEC-1).

## Opérations courantes

```bash
# État / logs
railway status
railway logs

# Reconstruire le modèle analytique d'un tenant (rejeu d'événements)
#   (exécuté dans le conteneur ; la base Railway est interne)
railway run php bin/console app:analytics:rebuild <tenant-uuid>   # nécessite un accès réseau à la base

# Initialiser la matrice de rôles d'un tenant
railway run php bin/console app:tenant:init-roles <tenant-uuid>
```

> Note : `railway run` exécute la commande **localement** avec les variables du service ; la base `postgres.railway.internal` n'est joignable que depuis le réseau Railway. Pour les commandes touchant la base, préférer une exécution dans le conteneur (console Railway) ou via une tâche de déploiement.
