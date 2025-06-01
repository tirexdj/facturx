#!/bin/bash

# Script pour créer la base de données de test
echo "Création de la base de données de test..."

# Créer la base de données de test si elle n'existe pas
mysql -u root -e "CREATE DATABASE IF NOT EXISTS facturx_test;" 2>/dev/null || echo "Base de données existante ou erreur de connexion"

# Lancer les migrations sur la base de test
php artisan migrate:fresh --env=testing --database=mysql

echo "Base de données de test prête!"
