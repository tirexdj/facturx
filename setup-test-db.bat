@echo off
REM Script pour créer la base de données de test sous Windows

echo Création de la base de données de test...

REM Créer la base de données de test si elle n'existe pas
mysql -u root -e "CREATE DATABASE IF NOT EXISTS facturx_test;" 2>nul || echo Base de données existante ou erreur de connexion

REM Lancer les migrations sur la base de test
php artisan migrate:fresh --env=testing --database=mysql

echo Base de données de test prête!
pause
