# Makefile pour FacturX
# Facilite la gestion du projet en local et en production

.PHONY: help install start stop restart logs shell test migrate seed fresh deploy

# Variables
DOCKER_COMPOSE = docker-compose
DOCKER_COMPOSE_EXEC = $(DOCKER_COMPOSE) exec app
PHP_CONTAINER = facturx_app

# Couleurs pour les messages
RED = \033[0;31m
GREEN = \033[0;32m
YELLOW = \033[0;33m
BLUE = \033[0;34m
NC = \033[0m # No Color

# Commande par défaut
help: ## Affiche cette aide
	@echo "$(BLUE)FacturX - Commandes disponibles:$(NC)"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "$(YELLOW)%-20s$(NC) %s\n", $$1, $$2}'

install: ## Installation complète du projet
	@echo "$(GREEN)🚀 Installation de FacturX...$(NC)"
	@$(DOCKER_COMPOSE) build
	@$(DOCKER_COMPOSE) up -d
	@$(DOCKER_COMPOSE_EXEC) composer install
	@$(DOCKER_COMPOSE_EXEC) cp .env.example .env
	@$(DOCKER_COMPOSE_EXEC) php artisan key:generate
	@make migrate
	@make seed
	@echo "$(GREEN)✅ Installation terminée !$(NC)"
	@echo "$(BLUE)Accès: http://localhost:8000$(NC)"

start: ## Démarre tous les services
	@echo "$(GREEN)▶️  Démarrage des services...$(NC)"
	@$(DOCKER_COMPOSE) up -d

stop: ## Arrête tous les services
	@echo "$(RED)⏹️  Arrêt des services...$(NC)"
	@$(DOCKER_COMPOSE) down

restart: ## Redémarre tous les services
	@echo "$(YELLOW)🔄 Redémarrage des services...$(NC)"
	@$(DOCKER_COMPOSE) restart

rebuild: ## Reconstruit et redémarre les conteneurs
	@echo "$(YELLOW)🔨 Reconstruction des conteneurs...$(NC)"
	@$(DOCKER_COMPOSE) down
	@$(DOCKER_COMPOSE) build --no-cache
	@$(DOCKER_COMPOSE) up -d

logs: ## Affiche les logs de tous les services
	@$(DOCKER_COMPOSE) logs -f

logs-app: ## Affiche les logs de l'application Laravel
	@$(DOCKER_COMPOSE) logs -f app

logs-nginx: ## Affiche les logs du serveur web
	@$(DOCKER_COMPOSE) logs -f nginx

logs-postgres: ## Affiche les logs de PostgreSQL
	@$(DOCKER_COMPOSE) logs -f postgres

shell: ## Accède au shell du conteneur PHP
	@echo "$(BLUE)🐚 Accès au shell...$(NC)"
	@$(DOCKER_COMPOSE_EXEC) /bin/sh

shell-root: ## Accède au shell en tant que root
	@$(DOCKER_COMPOSE) exec --user root app /bin/sh

# Commandes Laravel
migrate: ## Exécute les migrations
	@echo "$(BLUE)📊 Exécution des migrations...$(NC)"
	@$(DOCKER_COMPOSE_EXEC) php artisan migrate

migrate-fresh: ## Supprime et recrée toutes les tables
	@echo "$(YELLOW)🔄 Recréation complète de la base...$(NC)"
	@$(DOCKER_COMPOSE_EXEC) php artisan migrate:fresh

seed: ## Exécute les seeders
	@echo "$(BLUE)🌱 Ajout des données de test...$(NC)"
	@$(DOCKER_COMPOSE_EXEC) php artisan db:seed

fresh: migrate-fresh seed ## Migration fresh + seed

# Tests
test: ## Exécute tous les tests
	@echo "$(BLUE)🧪 Exécution des tests...$(NC)"
	@$(DOCKER_COMPOSE_EXEC) php artisan test

test-unit: ## Exécute uniquement les tests unitaires
	@$(DOCKER_COMPOSE_EXEC) php artisan test --testsuite=Unit

test-feature: ## Exécute uniquement les tests fonctionnels
	@$(DOCKER_COMPOSE_EXEC) php artisan test --testsuite=Feature

test-coverage: ## Exécute les tests avec couverture de code
	@$(DOCKER_COMPOSE_EXEC) php artisan test --coverage-html coverage

# Qualité de code
lint: ## Vérifie le style de code avec PHP CS Fixer
	@echo "$(BLUE)🔍 Vérification du style de code...$(NC)"
	@$(DOCKER_COMPOSE_EXEC) vendor/bin/php-cs-fixer fix --dry-run --diff

lint-fix: ## Corrige automatiquement le style de code
	@echo "$(YELLOW)🔧 Correction du style de code...$(NC)"
	@$(DOCKER_COMPOSE_EXEC) vendor/bin/php-cs-fixer fix

analyze: ## Analyse statique avec PHPStan
	@echo "$(BLUE)🔍 Analyse statique du code...$(NC)"
	@$(DOCKER_COMPOSE_EXEC) vendor/bin/phpstan analyse

rector: ## Analyse et suggestions d'amélioration avec Rector
	@$(DOCKER_COMPOSE_EXEC) vendor/bin/rector process --dry-run

rector-fix: ## Applique les améliorations Rector
	@$(DOCKER_COMPOSE_EXEC) vendor/bin/rector process

# Cache et optimisations
cache-clear: ## Vide tous les caches
	@echo "$(YELLOW)🧹 Vidage des caches...$(NC)"
	@$(DOCKER_COMPOSE_EXEC) php artisan cache:clear
	@$(DOCKER_COMPOSE_EXEC) php artisan config:clear
	@$(DOCKER_COMPOSE_EXEC) php artisan route:clear
	@$(DOCKER_COMPOSE_EXEC) php artisan view:clear

cache-warm: ## Préchauffe les caches
	@echo "$(GREEN)⚡ Préchauffage des caches...$(NC)"
	@$(DOCKER_COMPOSE_EXEC) php artisan config:cache
	@$(DOCKER_COMPOSE_EXEC) php artisan route:cache
	@$(DOCKER_COMPOSE_EXEC) php artisan view:cache

optimize: cache-clear cache-warm ## Optimise l'application (clear + warm cache)

# Base de données
db-reset: ## Remet à zéro la base de données
	@echo "$(RED)⚠️  Remise à zéro de la base de données...$(NC)"
	@$(DOCKER_COMPOSE_EXEC) php artisan migrate:fresh --seed

db-backup: ## Sauvegarde la base de données
	@echo "$(BLUE)💾 Sauvegarde de la base de données...$(NC)"
	@docker exec facturx_postgres pg_dump -U facturx facturx > backup_$(shell date +%Y%m%d_%H%M%S).sql

# Frontend
npm-install: ## Installe les dépendances Node.js
	@echo "$(BLUE)📦 Installation des dépendances frontend...$(NC)"
	@$(DOCKER_COMPOSE) exec node npm install

npm-dev: ## Lance le serveur de développement Vite
	@echo "$(GREEN)🎨 Démarrage du serveur de développement...$(NC)"
	@$(DOCKER_COMPOSE) exec node npm run dev

npm-build: ## Compile les assets pour la production
	@echo "$(BLUE)🔨 Compilation des assets...$(NC)"
	@$(DOCKER_COMPOSE) exec node npm run build

npm-watch: ## Lance la compilation en mode watch
	@$(DOCKER_COMPOSE) exec node npm run watch

# Facturation électronique
ppf-connect: ## Teste la connexion au PPF
	@echo "$(BLUE)🔗 Test de connexion au PPF...$(NC)"
	@$(DOCKER_COMPOSE_EXEC) php artisan facturx:ppf:test

pdp-setup: ## Configure la connexion à une PDP
	@echo "$(BLUE)⚙️  Configuration PDP...$(NC)"
	@$(DOCKER_COMPOSE_EXEC) php artisan facturx:pdp:setup

invoice-test: ## Teste la génération d'une facture électronique
	@echo "$(BLUE)📄 Test de génération de facture...$(NC)"
	@$(DOCKER_COMPOSE_EXEC) php artisan facturx:invoice:test

# Production
prod-build: ## Construit l'image de production
	@echo "$(GREEN)🏗️  Construction de l'image de production...$(NC)"
	@docker build --target production -t facturx:latest .

prod-deploy: ## Déploie en production
	@echo "$(RED)🚀 Déploiement en production...$(NC)"
	@./scripts/deploy.sh production

# Monitoring et maintenance
health-check: ## Vérifie l'état de l'application
	@echo "$(BLUE)💊 Vérification de l'état...$(NC)"
	@curl -f http://localhost:8000/health || echo "$(RED)❌ Service indisponible$(NC)"

queue-status: ## Affiche le statut des queues
	@$(DOCKER_COMPOSE_EXEC) php artisan queue:monitor

queue-restart: ## Redémarre les workers de queue
	@$(DOCKER_COMPOSE_EXEC) php artisan queue:restart

horizon-status: ## Affiche le statut d'Horizon
	@$(DOCKER_COMPOSE_EXEC) php artisan horizon:status

# Sécurité
security-check: ## Vérifie les vulnérabilités de sécurité
	@echo "$(BLUE)🛡️  Vérification de sécurité...$(NC)"
	@$(DOCKER_COMPOSE_EXEC) composer audit

permissions-fix: ## Corrige les permissions des fichiers
	@echo "$(YELLOW)🔒 Correction des permissions...$(NC)"
	@$(DOCKER_COMPOSE) exec --user root app chown -R laravel:laravel /var/www
	@$(DOCKER_COMPOSE) exec --user root app chmod -R 755 /var/www/storage /var/www/bootstrap/cache

# Documentation
docs-build: ## Génère la documentation de l'API
	@echo "$(BLUE)📚 Génération de la documentation...$(NC)"
	@$(DOCKER_COMPOSE_EXEC) php artisan l5-swagger:generate

docs-serve: ## Lance le serveur de documentation
	@echo "$(GREEN)📖 Documentation disponible sur http://localhost:8000/docs$(NC)"

# Utilitaires
clean: ## Nettoie les fichiers temporaires
	@echo "$(YELLOW)🧹 Nettoyage...$(NC)"
	@$(DOCKER_COMPOSE_EXEC) rm -rf storage/logs/*.log
	@$(DOCKER_COMPOSE_EXEC) rm -rf storage/framework/cache/data/*
	@$(DOCKER_COMPOSE_EXEC) rm -rf storage/framework/sessions/*
	@$(DOCKER_COMPOSE_EXEC) rm -rf storage/framework/views/*

logs-clean: ## Nettoie les fichiers de logs
	@echo "$(YELLOW)🗑️  Nettoyage des logs...$(NC)"
	@$(DOCKER_COMPOSE_EXEC) find storage/logs -name "*.log" -type f -delete

update: ## Met à jour les dépendances
	@echo "$(BLUE)📦 Mise à jour des dépendances...$(NC)"
	@$(DOCKER_COMPOSE_EXEC) composer update
	@$(DOCKER_COMPOSE) exec node npm update

backup: db-backup ## Sauvegarde complète

status: ## Affiche le statut des services
	@echo "$(BLUE)📊 Statut des services:$(NC)"
	@$(DOCKER_COMPOSE) ps

# Développement spécifique FacturX
facturx-setup: ## Configuration spécifique FacturX
	@echo "$(GREEN)⚙️  Configuration FacturX...$(NC)"
	@$(DOCKER_COMPOSE_EXEC) php artisan facturx:install
	@$(DOCKER_COMPOSE_EXEC) php artisan facturx:config:validate

demo-data: ## Charge les données de démonstration
	@echo "$(BLUE)🎭 Chargement des données de démo...$(NC)"
	@$(DOCKER_COMPOSE_EXEC) php artisan db:seed --class=DemoDataSeeder

compliance-check: ## Vérifie la conformité réglementaire
	@echo "$(BLUE)⚖️  Vérification de conformité...$(NC)"
	@$(DOCKER_COMPOSE_EXEC) php artisan facturx:compliance:check

# Aide contextuelle
laravel-help: ## Affiche l'aide Laravel
	@$(DOCKER_COMPOSE_EXEC) php artisan list

composer-help: ## Affiche l'aide Composer
	@$(DOCKER_COMPOSE_EXEC) composer --help

# Raccourcis pratiques
dev: start npm-dev ## Lance l'environnement de développement complet
build: npm-build optimize ## Construit l'application pour la production
ci: test lint analyze ## Exécute tous les contrôles de qualité
deploy: prod-build prod-deploy ## Processus de déploiement complet
