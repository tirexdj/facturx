<?php

/**
 * Script de réparation d'urgence pour les problèmes de tests
 * Lance automatiquement les corrections les plus courantes
 */

echo "=== RÉPARATION D'URGENCE - Tests FacturX ===\n\n";

function executeCommand($command, $description) {
    echo "⚡ $description...\n";
    $output = [];
    $returnCode = 0;
    exec($command . ' 2>&1', $output, $returnCode);
    
    if ($returnCode === 0) {
        echo "   ✅ Succès\n";
        return true;
    } else {
        echo "   ❌ Échec: " . implode('\n', $output) . "\n";
        return false;
    }
}

function testDatabaseConnection() {
    echo "🔍 Test de connexion base de données...\n";
    try {
        $pdo = new PDO('sqlite::memory:');
        echo "   ✅ SQLite accessible\n";
        return true;
    } catch (Exception $e) {
        echo "   ❌ SQLite inaccessible: " . $e->getMessage() . "\n";
        return false;
    }
}

// Étape 1: Nettoyer la configuration Laravel
echo "1️⃣ NETTOYAGE DE LA CONFIGURATION\n";
echo "================================\n";

executeCommand('php artisan config:clear', 'Vidage du cache de configuration');
executeCommand('php artisan cache:clear', 'Vidage du cache général');
executeCommand('php artisan route:clear', 'Vidage du cache des routes');
executeCommand('php artisan view:clear', 'Vidage du cache des vues');

echo "\n";

// Étape 2: Vérifier les prérequis
echo "2️⃣ VÉRIFICATION DES PRÉREQUIS\n";
echo "==============================\n";

$sqliteOk = testDatabaseConnection();
$configOk = file_exists('phpunit.xml');
$envTestingOk = file_exists('.env.testing');

echo "   📋 SQLite: " . ($sqliteOk ? "✅" : "❌") . "\n";
echo "   📋 phpunit.xml: " . ($configOk ? "✅" : "❌") . "\n";
echo "   📋 .env.testing: " . ($envTestingOk ? "✅" : "❌") . "\n";

echo "\n";

// Étape 3: Réparer les fichiers de configuration si nécessaire
echo "3️⃣ RÉPARATION DES FICHIERS DE CONFIGURATION\n";
echo "===========================================\n";

if (!$envTestingOk) {
    echo "📝 Création du fichier .env.testing...\n";
    $envContent = "APP_NAME=FacturX
APP_ENV=testing
APP_KEY=base64:test_key_here
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=sqlite
DB_DATABASE=:memory:

CACHE_STORE=array
QUEUE_CONNECTION=sync
SESSION_DRIVER=array
MAIL_MAILER=array

BCRYPT_ROUNDS=4
";
    file_put_contents('.env.testing', $envContent);
    echo "   ✅ Fichier .env.testing créé\n";
}

if (!$configOk) {
    echo "❌ Fichier phpunit.xml manquant - veuillez le créer manuellement\n";
}

echo "\n";

// Étape 4: Test de base minimal
echo "4️⃣ TESTS DE BASE\n";
echo "================\n";

$basicTestOk = executeCommand(
    'php artisan test tests/Feature/DatabaseConnectionTest.php::test_database_connection_works --stop-on-failure',
    'Test de connexion de base'
);

if (!$basicTestOk) {
    echo "🚨 PROBLÈME CRITIQUE: Le test de base échoue\n";
    echo "   Causes possibles:\n";
    echo "   - Extension SQLite manquante\n";
    echo "   - Problème de configuration Laravel\n";
    echo "   - Fichiers de migration corrompus\n";
    
    echo "\n📞 SOLUTIONS D'URGENCE:\n";
    echo "1. Réinstaller les extensions PHP: php -m | grep sqlite\n";
    echo "2. Régénérer la clé d'application: php artisan key:generate --env=testing\n";
    echo "3. Vérifier les permissions: ls -la storage/\n";
    echo "4. Recréer les migrations: php artisan migrate:fresh --env=testing\n";
    
    exit(1);
}

echo "\n";

// Étape 5: Test de transaction et isolation
echo "5️⃣ TESTS DE TRANSACTIONS ET ISOLATION\n";
echo "=====================================\n";

$transactionTestOk = executeCommand(
    'php artisan test tests/Feature/TransactionIsolationTest.php --stop-on-failure',
    'Tests d\'isolation des transactions'
);

if (!$transactionTestOk) {
    echo "⚠️  PROBLÈME: Tests d'isolation échouent\n";
    echo "   Solutions:\n";
    echo "   1. Relancer ce script\n";
    echo "   2. Redémarrer l'environnement de développement\n";
    echo "   3. Vérifier les logs: storage/logs/laravel.log\n";
}

echo "\n";

// Étape 6: Test complet d'un module
echo "6️⃣ TEST D'UN MODULE COMPLET\n";
echo "===========================\n";

$moduleTestOk = executeCommand(
    'php artisan test tests/Feature/Api/V1/Customer/ClientTest.php::test_can_list_clients --stop-on-failure',
    'Test d\'un module métier'
);

echo "\n";

// Résumé final
echo "📊 RÉSUMÉ DE LA RÉPARATION\n";
echo "=========================\n";

$scores = [
    'Configuration' => $sqliteOk && $configOk && $envTestingOk,
    'Test de base' => $basicTestOk,
    'Isolation' => $transactionTestOk,
    'Module métier' => $moduleTestOk,
];

$totalOk = array_sum($scores);
$total = count($scores);

foreach ($scores as $category => $ok) {
    echo sprintf("   %-15s: %s\n", $category, $ok ? "✅" : "❌");
}

echo "\n";
echo sprintf("🎯 Score global: %d/%d (%d%%)\n", $totalOk, $total, round(($totalOk / $total) * 100));

if ($totalOk === $total) {
    echo "\n🎉 RÉPARATION RÉUSSIE! Tous les tests fonctionnent.\n";
    echo "🚀 Vous pouvez maintenant lancer: php artisan test\n";
} elseif ($totalOk >= 2) {
    echo "\n✅ RÉPARATION PARTIELLE. Les problèmes de base sont résolus.\n";
    echo "🔧 Quelques ajustements peuvent être nécessaires.\n";
} else {
    echo "\n🚨 RÉPARATION INCOMPLÈTE. Problèmes persistants.\n";
    echo "📞 Contactez le support technique ou vérifiez:\n";
    echo "   - Extensions PHP\n";
    echo "   - Permissions de fichiers\n";
    echo "   - Configuration Laragon/serveur\n";
}

echo "\n=== FIN DE LA RÉPARATION ===\n";
