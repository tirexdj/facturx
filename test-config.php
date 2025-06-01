<?php

// Test rapide pour vérifier que la configuration des tests fonctionne
echo "Test de configuration des tests...\n";

// Tester la clé de chiffrement
$key = 'base64:SGVsbG9Xb3JsZEhlbGxvV29ybGRIZWxsb1dvcmxkSGVsbG9Xb3JsZA==';
$decoded = base64_decode(substr($key, 7));
echo "Longueur de clé décodée: " . strlen($decoded) . " bytes\n";

if (strlen($decoded) === 32) {
    echo "✓ Clé de chiffrement valide (32 bytes)\n";
} else {
    echo "✗ Clé de chiffrement invalide\n";
}

// Vérifier que les classes existent
$classes = [
    'Tests\\TestCase',
    'Tests\\Traits\\WithSeededDatabase',
    'Tests\\Traits\\ManagesTestTransactions',
];

foreach ($classes as $class) {
    if (class_exists($class)) {
        echo "✓ Classe $class existe\n";
    } else {
        echo "✗ Classe $class manquante\n";
    }
}

echo "\nTest terminé.\n";
