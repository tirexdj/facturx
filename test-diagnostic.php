<?php

// Script de diagnostic pour les tests
echo "=== Diagnostic des Tests FacturX ===\n\n";

// Vérification de PHP et extensions
echo "1. Version PHP: " . phpversion() . "\n";
echo "2. Extensions PDO: " . (extension_loaded('pdo') ? 'OK' : 'MANQUANTE') . "\n";
echo "3. Extension SQLite: " . (extension_loaded('pdo_sqlite') ? 'OK' : 'MANQUANTE') . "\n";
echo "4. Extension MySQL: " . (extension_loaded('pdo_mysql') ? 'OK' : 'MANQUANTE') . "\n\n";

// Vérification des chemins
echo "5. Répertoire de travail: " . getcwd() . "\n";
echo "6. Fichier .env.testing: " . (file_exists('.env.testing') ? 'Trouvé' : 'Non trouvé') . "\n";
echo "7. Fichier phpunit.xml: " . (file_exists('phpunit.xml') ? 'Trouvé' : 'Non trouvé') . "\n\n";

// Tentative de connexion SQLite
echo "8. Test de connexion SQLite en mémoire:\n";
try {
    $pdo = new PDO('sqlite::memory:');
    echo "   ✓ Connexion SQLite réussie\n";
    
    // Test de création de table
    $pdo->exec('CREATE TABLE test (id INTEGER PRIMARY KEY)');
    echo "   ✓ Création de table réussie\n";
    
    // Test d'insertion
    $pdo->exec('INSERT INTO test VALUES (1)');
    echo "   ✓ Insertion réussie\n";
    
    // Test de sélection
    $result = $pdo->query('SELECT COUNT(*) FROM test')->fetchColumn();
    echo "   ✓ Sélection réussie (résultat: $result)\n";
    
    // Test de transaction
    $pdo->beginTransaction();
    echo "   ✓ Transaction démarrée\n";
    $pdo->rollBack();
    echo "   ✓ Transaction annulée\n";
    
    // Test de PRAGMA
    $pdo->exec('PRAGMA foreign_keys=ON');
    $foreignKeys = $pdo->query('PRAGMA foreign_keys')->fetchColumn();
    echo "   ✓ PRAGMA configuré (foreign_keys: $foreignKeys)\n";
    
    // Test de duplication de table (pour reproduire l'erreur)
    try {
        $pdo->exec('CREATE TABLE test (id INTEGER PRIMARY KEY)');
        echo "   ⚠ Duplication de table possible (peut causer des erreurs)\n";
    } catch (Exception $e) {
        echo "   ✓ Protection contre duplication de table active\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ Erreur SQLite: " . $e->getMessage() . "\n";
}

echo "\n";

// Test de connexion MySQL (si disponible)
if (extension_loaded('pdo_mysql')) {
    echo "9. Test de connexion MySQL locale:\n";
    try {
        $pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '');
        echo "   ✓ Connexion MySQL réussie\n";
        
        // Tester création de base de test
        try {
            $pdo->exec('CREATE DATABASE IF NOT EXISTS facturx_test');
            echo "   ✓ Base de données de test créée/vérifiée\n";
        } catch (Exception $e) {
            echo "   ⚠ Impossible de créer la base de test: " . $e->getMessage() . "\n";
        }
    } catch (Exception $e) {
        echo "   ✗ Erreur MySQL: " . $e->getMessage() . "\n";
    }
} else {
    echo "9. Extension MySQL non disponible\n";
}

echo "\n";

// Test spécifique pour l'erreur des migrations
echo "10. Test de détection du problème 'migrations already exists':\n";
try {
    $pdo = new PDO('sqlite::memory:');
    
    // Simuler le problème
    $pdo->exec('CREATE TABLE migrations (id INTEGER PRIMARY KEY AUTOINCREMENT, migration VARCHAR, batch INTEGER)');
    echo "   ✓ Table migrations créée\n";
    
    // Tenter de la recréer (devrait échouer)
    try {
        $pdo->exec('CREATE TABLE migrations (id INTEGER PRIMARY KEY AUTOINCREMENT, migration VARCHAR, batch INTEGER)');
        echo "   ✗ PROBLÈME: Table migrations créée deux fois (erreur possible)\n";
    } catch (Exception $e) {
        echo "   ✓ Protection active contre la duplication: " . $e->getMessage() . "\n";
    }
    
    // Test de nettoyage
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll();
    echo "   ✓ Tables détectées: " . count($tables) . "\n";
    
    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `{$table['name']}`");
    }
    
    $tablesAfter = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll();
    echo "   ✓ Tables après nettoyage: " . count($tablesAfter) . "\n";
    
} catch (Exception $e) {
    echo "   ✗ Erreur dans le test: " . $e->getMessage() . "\n";
}

echo "\n";

// Recommandations
echo "=== Recommandations ===\n";
if (!extension_loaded('pdo_sqlite')) {
    echo "- Installer l'extension PHP SQLite (php-sqlite3)\n";
}
if (!file_exists('.env.testing')) {
    echo "- Créer un fichier .env.testing avec les paramètres de test\n";
}
if (!file_exists('phpunit.xml')) {
    echo "- Vérifier la configuration phpunit.xml\n";
}

// Vérification de la configuration Laravel
echo "\n=== Test de configuration Laravel ===\n";
if (file_exists('artisan')) {
    echo "11. Test des commandes Laravel:\n";
    
    // Test de la commande config:cache
    $output = [];
    $returnCode = 0;
    exec('php artisan config:show database.default 2>&1', $output, $returnCode);
    
    if ($returnCode === 0) {
        echo "   ✓ Laravel accessible\n";
        echo "   ✓ Configuration DB: " . trim(implode(' ', $output)) . "\n";
    } else {
        echo "   ⚠ Problème avec Laravel: " . implode(' ', $output) . "\n";
    }
} else {
    echo "11. Fichier artisan non trouvé (pas dans un projet Laravel?)\n";
}

echo "\n=== Commandes suggérées ===\n";
echo "# Nettoyer la configuration Laravel\n";
echo "php artisan config:clear\n";
echo "php artisan cache:clear\n";
echo "\n";
echo "# Tester la connexion de base de données\n";
echo "php artisan test tests/Feature/DatabaseConnectionTest.php --verbose\n\n";
echo "# Lancer un test simple\n";
echo "php artisan test tests/Feature/Api/V1/Customer/ClientTest.php::test_can_list_clients --verbose\n\n";
echo "# Lancer tous les tests avec détails\n";
echo "php artisan test --verbose\n\n";
echo "# Debug d'un test spécifique\n";
echo "php artisan test tests/Feature/DatabaseConnectionTest.php::test_database_transactions_work --verbose\n\n";

echo "=== Fin du diagnostic ===\n";
