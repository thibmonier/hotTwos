# US-008 / ADR-2 — image FrankenPHP. Sert de base au dev (US-007) et au staging (Railway).
FROM dunglas/frankenphp:1-php8.5 AS base

ENV SERVER_NAME=:8080
ENV APP_ENV=prod
ENV APP_DEBUG=0
# Le build tourne en root : autoriser Composer à exécuter les plugins (symfony/runtime, flex),
# sans quoi vendor/autoload_runtime.php n'est pas généré.
ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /app

# Extensions PHP (PostgreSQL, intl, opcache) + Composer.
RUN install-php-extensions pdo_pgsql intl opcache zip @composer

# Caddyfile custom (désactive h2c — évite la corruption de méthode HTTP derrière un proxy).
COPY frankenphp/Caddyfile /etc/frankenphp/Caddyfile

# Code applicatif.
COPY . .

# Dépendances de prod sans les auto-scripts (symfony-cmd indisponible au build),
# puis génération de l'autoload optimisé + de vendor/autoload_runtime.php (plugin symfony/runtime).
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader \
 && composer dump-autoload --no-dev --optimize \
 && mkdir -p var/cache var/log \
 && php bin/console importmap:install \
 && php bin/console asset-map:compile \
 && php bin/console cache:clear

# Mode worker (ADR-2) activé via frankenphp/Caddyfile (bridge manuel dans public/index.php,
# compatible Symfony 8). L'état inter-requêtes est réinitialisé entre deux requêtes
# (services kernel.reset — RSQ-15/ARC-47).

EXPOSE 8080
