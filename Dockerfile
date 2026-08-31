# Amorce ADR-2 — FrankenPHP en mode worker. Image finalisée et testée en US-007 (conteneurs).
# La même image sert au développement et à la production (parité worker, ARC-86).
FROM dunglas/frankenphp:1-php8.4 AS base

ENV SERVER_NAME=:8080
WORKDIR /app

# Extensions PHP requises (PostgreSQL + pgvector, intl, opcache…)
RUN install-php-extensions \
    pdo_pgsql \
    intl \
    opcache \
    zip

# Dépendances (couche cache)
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist || true

# Code applicatif
COPY . .

# Mode worker (ADR-2) : boucle l'application en mémoire entre les requêtes.
# Nécessite le bridge runtime/frankenphp-symfony (APP_RUNTIME=Runtime\FrankenPhpSymfony\Runtime)
# dont la version compatible Symfony 8.1 reste à résoudre (T-006-02). En attendant, l'image
# sert l'app en php_server classique (Caddyfile). Activer le worker en décommentant :
# ENV FRANKENPHP_CONFIG="worker ./public/index.php"

EXPOSE 8080
