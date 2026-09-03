# HotOnes — commandes de développement (US-007).
# `make help` liste les cibles.
#
# PRINCIPE (ARC-86, parité prod) : AUCUNE commande ne s'exécute sur la machine hôte.
# Tout (tests, qualité, composer, console) tourne dans un conteneur éphémère basé sur la
# même image que la production. Le poste local n'a besoin que de Docker.
.DEFAULT_GOAL := help
DC := docker compose

# Exécution one-off entièrement conteneurisée :
#   run --rm : conteneur jetable (supprimé en fin de commande) ;
#   -T       : sans pseudo-TTY (compatible scripts / hooks / CI) ;
#   la commande applicative (start.sh : migrations + worker) est contournée → pas d'APP_SECRET
#   requis pour l'outillage ; le service `database` est démarré automatiquement (depends_on).
APP := $(DC) run --rm -T app
# Variante interactive (shell, prompts) : avec TTY.
APP_IT := $(DC) run --rm app
# Version de l'image gitleaks (épinglée — pas de `latest`, règle 11 supply-chain).
GITLEAKS_IMAGE := zricethezav/gitleaks:v8.18.4

.PHONY: help up down build logs sh db-shell migrate fixtures composer console \
        test analyse deptrac rector rector-fix cs cs-fix secrets audit ci smoke

help: ## Affiche cette aide
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN{FS=":.*?## "}{printf "  \033[36m%-14s\033[0m %s\n", $$1, $$2}'

## — Orchestration —

up: ## Démarre l'environnement (build si nécessaire)
	$(DC) up -d --build

down: ## Arrête l'environnement
	$(DC) down

build: ## Reconstruit les images
	$(DC) build

logs: ## Suit les logs de l'app
	$(DC) logs -f app

sh: ## Ouvre un shell dans un conteneur app jetable
	$(APP_IT) sh

db-shell: ## Ouvre psql sur la base
	$(DC) exec database psql -U app -d app

## — Application (conteneurisé) —

migrate: ## Applique les migrations Doctrine
	$(APP) php bin/console doctrine:migrations:migrate --no-interaction

fixtures: ## Génère les jeux de données de test (3 tailles de tenant)
	$(APP) php bin/console app:fixtures:load --no-interaction

db-reset: ## Purge la base (hors migrations) et re-seed un tenant de démo unique
	$(DC) exec -T database psql -U app -d app < docker/db-reset.sql
	$(APP) php bin/console app:demo:seed
	$(DC) restart app

composer: ## Lance composer dans le conteneur (ex. make composer c="require foo/bar")
	$(APP) composer $(c)

console: ## Lance bin/console dans le conteneur (ex. make console c="debug:router")
	$(APP) php bin/console $(c)

## — Qualité (conteneurisé, miroir CI) —

test: ## Lance la suite de tests (config dist, déterministe / miroir CI)
	$(DC) run --rm -T -e APP_ENV=test app sh -lc 'php bin/console cache:clear --env=test --no-warmup >/dev/null && php bin/phpunit -c phpunit.dist.xml'

deptrac: ## Vérifie les frontières d'architecture
	$(APP) php vendor/bin/deptrac analyse

analyse: ## Analyse statique PHPStan niveau max
	$(APP) php vendor/bin/phpstan analyse --memory-limit=1G

rector: ## Détecte les modernisations/dépréciations (dry-run)
	$(APP) php vendor/bin/rector process --dry-run

rector-fix: ## Applique les modernisations Rector
	$(APP) php vendor/bin/rector process

cs: ## Vérifie le style de code (dry-run)
	$(DC) run --rm -T -e PHP_CS_FIXER_IGNORE_ENV=1 app php vendor/bin/php-cs-fixer fix --dry-run --diff

cs-fix: ## Applique le style de code
	$(DC) run --rm -T -e PHP_CS_FIXER_IGNORE_ENV=1 app php vendor/bin/php-cs-fixer fix

audit: ## Audit des vulnérabilités des dépendances (ENF-SEC-11)
	$(APP) composer audit

secrets: ## Détecte les secrets commités (gitleaks conteneurisé, US-007/US-009)
	docker run --rm -v "$(CURDIR):/repo:ro" $(GITLEAKS_IMAGE) detect --source=/repo --no-banner || true

tailwind: ## Build le CSS Tailwind (binaire autonome, sans Node.js — ADR-0019)
	$(APP) php bin/console tailwind:build --minify

ci: tailwind cs rector analyse deptrac test ## Enchaîne les vérifications bloquantes en local (miroir CI)
	@echo "✅ Vérifications locales OK"

smoke: ## Smoke de déploiement : vérifie les endpoints critiques (make smoke URL=https://…)
	$(APP) php scripts/smoke.php $(URL)
