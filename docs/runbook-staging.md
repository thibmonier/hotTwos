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
