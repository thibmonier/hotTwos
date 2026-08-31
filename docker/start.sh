#!/bin/sh
# US-008 / TECH-1 — démarrage du conteneur : applique les migrations puis lance le serveur.
# Idempotent. Tolérant à l'échec des migrations (le serveur démarre quand même pour que
# /health reste disponible et diagnosticable) — l'échec est journalisé.
set -e

# Échec explicite si une variable obligatoire manque (US-008, CA-4) — mieux vaut un
# déploiement qui échoue clairement qu'un service qui démarre à moitié configuré.
missing=""
for var in APP_SECRET DATABASE_URL; do
    eval "value=\${$var:-}"
    if [ -z "$value" ]; then
        missing="$missing $var"
    fi
done
if [ -n "$missing" ]; then
    echo "[start] ERREUR : variable(s) d'environnement obligatoire(s) manquante(s) :$missing" >&2
    echo "[start] Définir ces variables dans le service (Railway) avant déploiement (ARC-88)." >&2
    exit 1
fi

echo "[start] Application des migrations Doctrine…"
if php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration; then
    echo "[start] Migrations appliquées."
else
    echo "[start] AVERTISSEMENT : migrations en échec — démarrage du serveur malgré tout (voir logs ci-dessus)."
fi

echo "[start] Démarrage de FrankenPHP (mode worker)…"
exec frankenphp run --config /etc/frankenphp/Caddyfile --adapter caddyfile
