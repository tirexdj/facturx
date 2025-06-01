#!/bin/bash

echo "========================================"
echo "      DIAGNOSTIC ET TESTS FACTURX"
echo "========================================"
echo

echo "1. Execution du diagnostic..."
echo "----------------------------------------"
php test-diagnostic.php
echo

echo "2. Test de connexion de base de données..."
echo "----------------------------------------"
php artisan test tests/Feature/DatabaseConnectionTest.php --verbose
if [ $? -ne 0 ]; then
    echo "ERREUR: Test de connexion échoué"
    echo "Vérifiez que SQLite est installé correctement"
    read -p "Appuyez sur Entrée pour continuer..."
    exit 1
fi
echo

echo "3. Test d'un module simple..."
echo "----------------------------------------"
php artisan test tests/Feature/Api/V1/Customer/ClientTest.php::test_can_list_clients --verbose
if [ $? -ne 0 ]; then
    echo "ERREUR: Test simple échoué"
    echo
    echo "Solutions possibles:"
    echo "- Vérifier les migrations: php artisan migrate:fresh --env=testing"
    echo "- Vérifier les factories"
    echo "- Vérifier les permissions"
    read -p "Appuyez sur Entrée pour continuer..."
    exit 1
fi
echo

echo "4. Execution de tous les tests..."
echo "----------------------------------------"
php artisan test --verbose
echo

if [ $? -eq 0 ]; then
    echo "========================================"
    echo "      TOUS LES TESTS RÉUSSIS!"
    echo "========================================"
else
    echo "========================================"
    echo "      CERTAINS TESTS ONT ÉCHOUÉ"
    echo "========================================"
    echo
    echo "Consultez la sortie ci-dessus pour plus de détails"
fi

echo
read -p "Appuyez sur Entrée pour continuer..."
