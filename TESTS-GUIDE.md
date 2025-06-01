# Guide de résolution des problèmes de tests - FacturX

## Problème principal
Erreur : `SQLSTATE[HY000] [2002] Aucune connexion n'a pu être établie car l'ordinateur cible l'a expressément refusée`

## Solutions implémentées

### 1. 🔧 Corrections automatiques
- **TestCase.php** : Gestion automatique des transactions
- **ManagesTestTransactions.php** : Trait pour nettoyer les transactions
- **BaseApiTest.php** : Vérification de santé de la DB
- **phpunit.xml** : Configuration SQLite pour les tests
- **.env.testing** : Variables d'environnement pour les tests

### 2. 📋 Vérification des prérequis

#### Commandes de diagnostic
```bash
# Diagnostic complet
php test-diagnostic.php

# Test de connexion DB
php artisan test tests/Feature/DatabaseConnectionTest.php

# Script automatisé (Windows)
run-tests.bat

# Script automatisé (Linux/Mac)
chmod +x run-tests.sh
./run-tests.sh
```

#### Extensions PHP requises
```bash
# Vérifier les extensions
php -m | grep -E "(pdo|sqlite)"

# Installer SQLite (Ubuntu/Debian)
sudo apt-get install php-sqlite3

# Installer SQLite (CentOS/RHEL)
sudo yum install php-pdo

# Installer SQLite (Windows - Laragon)
# Généralement déjà inclus
```

### 3. 🔍 Étapes de diagnostic

#### Étape 1 : Vérifier l'environnement
```bash
# Vérifier que SQLite fonctionne
php -r "var_dump(extension_loaded('pdo_sqlite'));"

# Vérifier les fichiers de config
ls -la .env.testing phpunit.xml
```

#### Étape 2 : Tester la base de données
```bash
# Test de connexion simple
php artisan test tests/Feature/DatabaseConnectionTest.php --verbose
```

#### Étape 3 : Tester un module
```bash
# Test d'un endpoint simple
php artisan test tests/Feature/Api/V1/Customer/ClientTest.php::test_can_list_clients
```

#### Étape 4 : Tous les tests
```bash
# Lancer tous les tests
php artisan test --verbose
```

### 4. 🚀 Solutions par ordre de priorité

#### Solution A : SQLite (Recommandée)
✅ **Avantages** : Rapide, pas de serveur requis, isolation parfaite
- Configuration : Déjà implémentée
- Prérequis : Extension `pdo_sqlite` (généralement incluse)

#### Solution B : MySQL avec Laragon
⚠️ **Si SQLite ne fonctionne pas**
1. Démarrer MySQL dans Laragon
2. Créer la base de test :
   ```sql
   CREATE DATABASE facturx_test;
   ```
3. Modifier `phpunit.xml` :
   ```xml
   <env name="DB_CONNECTION" value="mysql"/>
   <env name="DB_DATABASE" value="facturx_test"/>
   ```

#### Solution C : Configuration personnalisée
🔧 **Pour environnements spécifiques**
- Modifier `.env.testing` selon vos besoins
- Adapter les paramètres de connexion

### 5. 🐛 Problèmes courants et solutions

#### Erreur : "Extension PDO SQLite non chargée"
```bash
# Ubuntu/Debian
sudo apt-get install php-sqlite3
sudo service apache2 restart

# Windows (Laragon)
# Vérifier php.ini et décommenter :
extension=pdo_sqlite
```

#### Erreur : "Impossible de créer la base de données"
```bash
# Vérifier les permissions
ls -la database/
chmod 755 database/

# Ou utiliser SQLite en mémoire (recommandé pour tests)
# Déjà configuré dans phpunit.xml
```

#### Erreur : "Table migrations inexistante"
```bash
# Forcer la recréation
php artisan migrate:fresh --env=testing
```

#### Erreur : "Transaction déjà active"
✅ **Déjà corrigé** par les traits `ManagesTestTransactions`

### 6. 📊 Structure des tests

```
tests/
├── Feature/
│   ├── Api/V1/
│   │   ├── BaseApiTest.php         # Classe de base (✅ corrigée)
│   │   └── Customer/
│   │       └── ClientTest.php      # Tests client (✅ corrigée)
│   └── DatabaseConnectionTest.php  # Test de connexion (✅ nouveau)
├── Traits/
│   ├── ApiTestHelpers.php          # Helpers API
│   └── ManagesTestTransactions.php # Gestion transactions (✅ nouveau)
└── TestCase.php                    # Classe de base (✅ corrigée)
```

### 7. 🔥 Commandes de dépannage d'urgence

```bash
# Reset complet des tests
php artisan config:clear
php artisan cache:clear
composer dump-autoload

# Test minimal
php -r "
try {
    \$pdo = new PDO('sqlite::memory:');
    echo 'SQLite OK\n';
} catch (Exception \$e) {
    echo 'Erreur: ' . \$e->getMessage() . '\n';
}
"

# Vérifier la configuration Laravel
php artisan tinker
>>> DB::connection()->getPdo()
>>> exit
```

### 8. 📞 Support et débogage

#### Activer les logs détaillés
```bash
# Dans .env.testing
LOG_LEVEL=debug

# Voir les logs
tail -f storage/logs/laravel.log
```

#### Tests de régression
```bash
# Tester après chaque modification
php artisan test tests/Feature/DatabaseConnectionTest.php
```

## 🎯 Résumé des actions

1. ✅ **Diagnostic** : `php test-diagnostic.php`
2. ✅ **Test DB** : `php artisan test tests/Feature/DatabaseConnectionTest.php`
3. ✅ **Test simple** : Un test client basique
4. ✅ **Tous les tests** : `php artisan test`

**Les corrections sont déjà en place, vous devriez pouvoir lancer les tests avec SQLite directement !**
