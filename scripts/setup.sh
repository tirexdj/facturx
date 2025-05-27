#!/bin/bash

# Script de configuration pour l'environnement de développement FacturX
# Usage: ./scripts/setup.sh

set -e

echo "🚀 Configuration de l'environnement FacturX..."

# Vérifier que les dépendances sont installées
echo "📦 Vérification des dépendances..."

# Vérifier PHP 8.4
if ! php -v | grep -q "PHP 8.4"; then
    echo "❌ PHP 8.4 requis"
    exit 1
fi

# Vérifier Composer
if ! command -v composer &> /dev/null; then
    echo "❌ Composer n'est pas installé"
    exit 1
fi

# Vérifier Node.js
if ! command -v node &> /dev/null; then
    echo "❌ Node.js n'est pas installé"
    exit 1
fi

# Installation des dépendances PHP
echo "📥 Installation des dépendances PHP..."
composer install --optimize-autoloader

# Installation des dépendances Node.js
echo "📥 Installation des dépendances Node.js..."
npm install

# Configuration de l'environnement
echo "⚙️ Configuration de l'environnement..."
if [ ! -f .env ]; then
    cp .env.example .env
    echo "📄 Fichier .env créé"
fi

# Génération de la clé d'application
echo "🔑 Génération de la clé d'application..."
php artisan key:generate

# Configuration de la base de données
echo "🗃️ Configuration de la base de données..."
php artisan migrate:fresh --seed

# Cache des configurations
echo "⚡ Mise en cache des configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Construction des assets
echo "🎨 Construction des assets frontend..."
npm run build

# Configuration des permissions
echo "🔒 Configuration des permissions..."
chmod -R 755 storage bootstrap/cache

# Vérification finale
echo "✅ Tests de vérification..."
php artisan config:clear
php artisan test --testsuite=Unit

echo ""
echo "🎉 Configuration terminée avec succès !"
echo ""
echo "Pour démarrer le serveur de développement :"
echo "  php artisan serve"
echo ""
echo "Pour démarrer Vite (assets frontend) :"
echo "  npm run dev"
echo ""
echo "Accès à l'application : http://localhost:8000"
