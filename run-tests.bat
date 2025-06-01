@echo off
setlocal enabledelayedexpansion

echo ========================================
echo      DIAGNOSTIC ET TESTS FACTURX
echo           Version 2.0 - Corrigee
echo ========================================
echo.

echo 1. Verification rapide des prerequis...
echo ----------------------------------------
php -r "echo 'PHP: ' . phpversion() . PHP_EOL; echo 'SQLite: ' . (extension_loaded('pdo_sqlite') ? 'OK' : 'MANQUANT') . PHP_EOL;"
echo.

echo 2. Execution du diagnostic complet...
echo ----------------------------------------
php test-diagnostic.php
echo.

echo 3. Nettoyage de la configuration Laravel...
echo ----------------------------------------
php artisan config:clear >nul 2>&1
php artisan cache:clear >nul 2>&1
echo Configuration nettoyee.
echo.

echo 4. Test de connexion de base de donnees...
echo ----------------------------------------
php artisan test tests/Feature/DatabaseConnectionTest.php::test_database_connection_works --verbose
if !errorlevel! neq 0 (
    echo.
    echo ERREUR: Test de connexion de base echoue
    echo.
    echo Solutions possibles:
    echo - Verifier que SQLite est installe: php -m | grep sqlite
    echo - Relancer le script de reparation: php emergency-fix.php
    echo - Verifier les extensions PHP dans Laragon
    echo.
    pause
    exit /b 1
)
echo.

echo 5. Test d'isolation des transactions...
echo ----------------------------------------
php artisan test tests/Feature/TransactionIsolationTest.php --verbose
if !errorlevel! neq 0 (
    echo.
    echo ATTENTION: Probleme d'isolation detecte
    echo Lancement de la reparation d'urgence...
    echo.
    php emergency-fix.php
    echo.
    echo Nouveau test d'isolation...
    php artisan test tests/Feature/TransactionIsolationTest.php::test_no_active_transactions_at_start --verbose
    if !errorlevel! neq 0 (
        echo ERREUR PERSISTANTE: Probleme d'isolation non resolu
        pause
        exit /b 1
    )
)
echo.

echo 6. Test du probleme specifique migrations...
echo ----------------------------------------
php artisan test tests/Feature/DatabaseConnectionTest.php::test_database_transactions_work --verbose
if !errorlevel! neq 0 (
    echo.
    echo ERREUR: Le probleme original persiste
    echo Tentative de reparation...
    php emergency-fix.php
    pause
    exit /b 1
)
echo.

echo 7. Test d'un module simple...
echo ----------------------------------------
php artisan test tests/Feature/Api/V1/Customer/ClientTest.php::test_can_list_clients --verbose
if !errorlevel! neq 0 (
    echo.
    echo ERREUR: Test metier echoue
    echo.
    echo Solutions possibles:
    echo - Verifier les migrations: php artisan migrate:fresh --env=testing
    echo - Verifier les factories et seeders
    echo - Relancer la reparation: php emergency-fix.php
    echo.
    pause
    exit /b 1
)
echo.

echo 8. Execution de tous les tests...
echo ----------------------------------------
echo Lancement de la suite complete de tests...
echo.
php artisan test --verbose
set TEST_RESULT=!errorlevel!
echo.

if !TEST_RESULT! equ 0 (
    echo ========================================
    echo      TOUS LES TESTS REUSSIS!
    echo ========================================
    echo.
    echo ✅ Connexion base de donnees: OK
    echo ✅ Isolation des transactions: OK
    echo ✅ Tests metier: OK
    echo ✅ Suite complete: OK
    echo.
    echo 🎉 Votre environnement de test est parfaitement configure!
) else (
    echo ========================================
    echo      CERTAINS TESTS ONT ECHOUE
    echo ========================================
    echo.
    echo Solutions avancees:
    echo 1. Relancer la reparation complete: php emergency-fix.php
    echo 2. Verifier les logs: storage/logs/laravel.log
    echo 3. Tester individuellement: php artisan test --filter="nom_du_test"
    echo 4. Recreer la base: php artisan migrate:fresh --env=testing
    echo 5. Reinstaller les dependances: composer install
    echo.
    echo Consultez la sortie ci-dessus pour plus de details
)

echo.
echo ========================================
echo           RESUME TECHNIQUE
echo ========================================
echo.
echo Configuration utilisee:
echo - Base de donnees: SQLite en memoire
echo - Environment: testing
echo - Isolation: ManagesTestTransactions
echo - Corrections: Actives
echo.
echo Scripts disponibles:
echo - test-diagnostic.php    : Diagnostic complet
echo - emergency-fix.php      : Reparation d'urgence
echo - run-tests.bat          : Ce script
echo.
echo Tests specifiques disponibles:
echo - DatabaseConnectionTest : Tests de connexion
echo - TransactionIsolationTest: Tests d'isolation
echo - ClientTest             : Tests metier
echo.
echo Pour plus d'aide: TESTS-GUIDE.md
echo.
pause
