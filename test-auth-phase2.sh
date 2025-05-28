#!/bin/bash

echo "==================================="
echo "   FacturX - Tests Authentification"
echo "==================================="
echo ""

echo "1. Vérification de l'environnement..."
php --version
echo ""

echo "2. Installation des dépendances..."
composer install --no-interaction --prefer-dist --optimize-autoloader
echo ""

echo "3. Configuration de l'environnement de test..."
cp .env.testing.example .env.testing 2>/dev/null || echo "Fichier .env.testing.example non trouvé, utilisation de .env"
echo ""

echo "4. Génération de la clé d'application..."
php artisan key:generate --ansi
echo ""

echo "5. Exécution des migrations et seeders..."
php artisan migrate:fresh --seed --force
echo ""

echo "6. Configuration de Laravel Sanctum..."
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" --force
echo ""

echo "7. Nettoyage du cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
echo ""

echo "8. Exécution des tests d'authentification..."
echo "=========================================="
php artisan test tests/Feature/Api/V1/Auth/AuthTest.php --verbose
echo ""

echo "9. Test manuel des endpoints d'authentification..."
echo "=================================================="
echo ""

echo "Test 1: Health check"
curl -X GET "http://facturx.test/api/health" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json"
echo -e "\n"

echo "Test 2: Registration"
curl -X POST "http://facturx.test/api/v1/auth/register" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john.doe@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "company_name": "Test Company",
    "siren": "123456789",
    "siret": "12345678901234",
    "job_title": "CEO"
  }'
echo -e "\n"

echo "Test 3: Login"
curl -X POST "http://facturx.test/api/v1/auth/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john.doe@example.com",
    "password": "password123"
  }'
echo -e "\n"

echo "==================================="
echo "   Tests terminés !"
echo "==================================="
