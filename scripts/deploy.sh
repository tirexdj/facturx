#!/bin/bash

# Script de déploiement pour FacturX
# Usage: ./scripts/deploy.sh [environment]

set -e

ENVIRONMENT=${1:-production}

echo "🚀 Déploiement de FacturX en environnement: $ENVIRONMENT"

# Vérifications préalables
echo "🔍 Vérifications préalables..."

# Vérifier que nous sommes sur la bonne branche
if [ "$ENVIRONMENT" = "production" ]; then
    CURRENT_BRANCH=$(git branch --show-current)
    if [ "$CURRENT_BRANCH" != "main" ]; then
        echo "❌ Le déploiement en production doit être fait depuis la branche main"
        exit 1
    fi
fi

# Vérifier que le working directory est propre
if [ -n "$(git status --porcelain)" ]; then
    echo "❌ Le working directory n'est pas propre. Commitez ou stashez vos changements."
    exit 1
fi

# Installation des dépendances de production
echo "📦 Installation des dépendances..."
composer install --no-dev --optimize-autoloader --no-interaction

# Installation des dépendances Node.js et build
echo "🎨 Construction des assets..."
npm ci --production=false
npm run build

# Mise en cache des configurations
echo "⚡ Optimisation des performances..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Exécution des migrations
echo "🗃️ Mise à jour de la base de données..."
if [ "$ENVIRONMENT" = "production" ]; then
    read -p "⚠️  Voulez-vous exécuter les migrations en production ? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        php artisan migrate --force
    fi
else
    php artisan migrate --force
fi

# Nettoyage des caches applicatifs
echo "🧹 Nettoyage des caches..."
php artisan cache:clear
php artisan queue:restart

# Configuration des permissions
echo "🔒 Configuration des permissions..."
chmod -R 755 storage bootstrap/cache

# Tests de sanité
echo "🩺 Tests de sanité..."
php artisan config:show
php artisan route:list --compact

# Vérification du statut de l'application
echo "✅ Vérification du statut..."
php artisan about

echo ""
echo "🎉 Déploiement terminé avec succès !"
echo ""
echo "N'oubliez pas de :"
echo "  - Redémarrer les workers de queue si nécessaire"
echo "  - Vérifier les logs d'erreurs"
echo "  - Tester les fonctionnalités critiques"
