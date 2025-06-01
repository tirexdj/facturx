<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;

    /**
     * Indique si on doit exécuter les seeders par défaut.
     * Peut être override dans les tests spécifiques.
     */
    protected bool $seed = false;

    protected function setUp(): void
    {
        parent::setUp();
        
        // S'assurer que la base de données est correctement initialisée
        $this->ensureDatabaseIsSetup();
        
        // Optionnel : exécuter les seeders si nécessaire et disponibles
        if ($this->seed) {
            $this->seedEssentialData();
        }
    }

    /**
     * S'assurer que la base de données est correctement configurée.
     */
    protected function ensureDatabaseIsSetup(): void
    {
        try {
            // Vérifier la connexion
            $connection = DB::connection();
            $pdo = $connection->getPdo();
            
            if (!$pdo) {
                throw new \Exception('PDO connection not available');
            }

            // Vérifier que les tables principales existent
            $requiredTables = ['users', 'companies', 'plans'];
            $missingTables = [];
            
            foreach ($requiredTables as $table) {
                if (!Schema::hasTable($table)) {
                    $missingTables[] = $table;
                }
            }

            // Si des tables manquent, forcer la migration
            if (!empty($missingTables)) {
                $this->runFreshMigrations();
            }

        } catch (\Exception $e) {
            // En cas d'erreur, tenter de recréer complètement la base
            $this->runFreshMigrations();
        }
    }

    /**
     * Exécuter les migrations depuis zéro.
     */
    protected function runFreshMigrations(): void
    {
        try {
            // Nettoyer toutes les transactions en cours
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            // Si on utilise SQLite en mémoire, supprimer toutes les tables
            if ($this->usingSqliteMemory()) {
                $this->dropAllSqliteTables();
            }

            // Exécuter les migrations
            Artisan::call('migrate:fresh', [
                '--force' => true,
                '--seed' => false,
            ]);

        } catch (\Exception $e) {
            throw new \Exception('Failed to run fresh migrations: ' . $e->getMessage());
        }
    }

    /**
     * Supprimer toutes les tables SQLite.
     */
    protected function dropAllSqliteTables(): void
    {
        try {
            $tables = DB::select(
                "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"
            );

            foreach ($tables as $table) {
                DB::statement("DROP TABLE IF EXISTS `{$table->name}`");
            }
        } catch (\Exception $e) {
            // Ignorer les erreurs de suppression
        }
    }

    /**
     * Vérifier si on utilise SQLite en mémoire.
     */
    protected function usingSqliteMemory(): bool
    {
        return config('database.default') === 'sqlite' 
            && config('database.connections.sqlite.database') === ':memory:';
    }

    /**
     * Helper pour exécuter les seeders de base nécessaires aux tests.
     * Cette méthode est sécurisée et ne plantera pas si les seeders n'existent pas.
     */
    protected function seedEssentialData(): void
    {
        try {
            // Vérifier que les tables existent avant d'essayer de les seeder
            if (Schema::hasTable('features')) {
                $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\FeatureSeeder']);
            }
            if (Schema::hasTable('plans')) {
                $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\PlanSeeder']);
            }
            if (Schema::hasTable('roles')) {
                $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RoleSeeder']);
            }
        } catch (\Exception $e) {
            // Si les seeders échouent, continuer le test sans eux
            // Cela permet aux tests unitaires de fonctionner même si les seeders ont des problèmes
        }
    }

    /**
     * Créer un utilisateur directement en base de données pour les tests simples.
     * Cette méthode ne conflicte pas avec le trait ApiTestHelpers.
     */
    protected function createSimpleUser(): array
    {
        // S'assurer que la table users existe
        if (!Schema::hasTable('users')) {
            throw new \Exception('Users table does not exist. Database not properly migrated.');
        }

        $userData = [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('users')->insert($userData);

        return $userData;
    }

    /**
     * Helper pour obtenir les informations de debug sur la base de données.
     */
    protected function debugDatabaseState(): array
    {
        $state = [
            'connection' => config('database.default'),
            'database' => config('database.connections.' . config('database.default') . '.database'),
            'tables' => [],
            'pdo_available' => false,
            'transaction_level' => 0,
        ];

        try {
            $pdo = DB::connection()->getPdo();
            $state['pdo_available'] = $pdo !== null;
            $state['transaction_level'] = DB::transactionLevel();

            if (config('database.default') === 'sqlite') {
                $tables = DB::select(
                    "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"
                );
                $state['tables'] = array_map(fn($table) => $table->name, $tables);
            }
        } catch (\Exception $e) {
            $state['error'] = $e->getMessage();
        }

        return $state;
    }

    /**
     * Méthode pour vérifier l'état de la base avant un test.
     */
    protected function assertDatabaseReady(): void
    {
        $this->assertTrue(
            Schema::hasTable('users'), 
            'Users table should exist. Database state: ' . json_encode($this->debugDatabaseState())
        );
        
        $this->assertTrue(
            Schema::hasTable('companies'), 
            'Companies table should exist'
        );
        
        $this->assertTrue(
            Schema::hasTable('plans'), 
            'Plans table should exist'
        );
    }
}
