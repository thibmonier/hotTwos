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

# Mode worker : boucle l'application en mémoire entre les requêtes (ADR-2).
# APP_RUNTIME=Runtime\FrankenPhpSymfony\Runtime + FRANKENPHP_CONFIG "worker ./public/index.php"
# à activer en US-007 avec runtime/frankenphp-symfony (installer avec -W).
ENV FRANKENPHP_CONFIG="worker ./public/index.php"

EXPOSE 8080
