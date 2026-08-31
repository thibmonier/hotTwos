#!/bin/sh
# US-008 / TECH-1 — démarrage du conteneur : applique les migrations puis lance le serveur.
# Idempotent. Tolérant à l'échec des migrations (le serveur démarre quand même pour que
# /health reste disponible et diagnosticable) — l'échec est journalisé.
set -e

echo "[start] Application des migrations Doctrine…"
if php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration; then
    echo "[start] Migrations appliquées."
else
    echo "[start] AVERTISSEMENT : migrations en échec — démarrage du serveur malgré tout (voir logs ci-dessus)."
fi

echo "[start] Démarrage de FrankenPHP (mode worker)…"
exec frankenphp run --config /etc/frankenphp/Caddyfile --adapter caddyfile
