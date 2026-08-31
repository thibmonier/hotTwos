# HotOnes — commandes de développement (US-007).
# `make help` liste les cibles.
.DEFAULT_GOAL := help
DC := docker compose

.PHONY: help up down build logs sh db-shell migrate fixtures test analyse deptrac rector rector-fix secrets audit ci

help: ## Affiche cette aide
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN{FS=":.*?## "}{printf "  \033[36m%-14s\033[0m %s\n", $$1, $$2}'

up: ## Démarre l'environnement (build si nécessaire)
	$(DC) up -d --build

down: ## Arrête l'environnement
	$(DC) down

build: ## Reconstruit les images
	$(DC) build

logs: ## Suit les logs de l'app
	$(DC) logs -f app

sh: ## Ouvre un shell dans le conteneur app
	$(DC) exec app sh

db-shell: ## Ouvre psql sur la base
	$(DC) exec database psql -U app -d app

migrate: ## Applique les migrations Doctrine
	$(DC) exec app php bin/console doctrine:migrations:migrate --no-interaction

fixtures: ## Génère les jeux de données de test (3 tailles de tenant)
	$(DC) exec app php bin/console app:fixtures:load --no-interaction

test: ## Lance la suite de tests
	php bin/phpunit

deptrac: ## Vérifie les frontières d'architecture
	php vendor/bin/deptrac analyse

analyse: ## Analyse statique PHPStan niveau max
	php vendor/bin/phpstan analyse --memory-limit=1G

rector: ## Détecte les modernisations/dépréciations (dry-run)
	php vendor/bin/rector process --dry-run

rector-fix: ## Applique les modernisations Rector
	php vendor/bin/rector process

audit: ## Audit des vulnérabilités des dépendances (ENF-SEC-11)
	composer audit

secrets: ## Détecte les secrets commités (gitleaks, US-007/US-009)
	gitleaks detect --source . --no-banner || true

ci: analyse deptrac test ## Enchaîne les vérifications bloquantes en local (miroir CI)
	@echo "✅ Vérifications locales OK"
