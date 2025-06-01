<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class DatabaseDiagnosticTest extends TestCase
{
    /**
     * Test de diagnostic complet de la base de données.
     */
    public function test_database_diagnostic(): void
    {
        $state = $this->debugDatabaseState();
        
        // Afficher les informations de debug
        $this->addToAssertionCount(1); // Pour que PHPUnit compte ce test
        
        echo "\n=== DATABASE DIAGNOSTIC ===\n";
        echo "Connection: " . $state['connection'] . "\n";
        echo "Database: " . $state['database'] . "\n";
        echo "PDO Available: " . ($state['pdo_available'] ? 'YES' : 'NO') . "\n";
        echo "Transaction Level: " . ($state['transaction_level'] ?? 'N/A') . "\n";
        
        if (isset($state['error'])) {
            echo "ERROR: " . $state['error'] . "\n";
        }
        
        echo "Tables (" . count($state['tables']) . "):\n";
        foreach ($state['tables'] as $table) {
            echo "  - " . $table . "\n";
        }
        echo "=========================\n";
        
        // Assertions de base
        $this->assertTrue($state['pdo_available'], 'PDO should be available');
        $this->assertGreaterThan(0, count($state['tables']), 'Should have tables');
    }

    /**
     * Test de forcer la migration.
     */
    public function test_force_fresh_migration(): void
    {
        try {
            // Forcer une migration fresh
            $this->runFreshMigrations();
            
            // Vérifier que les tables existent maintenant
            $state = $this->debugDatabaseState();
            $this->assertGreaterThan(10, count($state['tables']), 'Should have many tables after fresh migration');
            
            // Vérifier les tables critiques
            $this->assertTrue(Schema::hasTable('users'));
            $this->assertTrue(Schema::hasTable('companies'));
            $this->assertTrue(Schema::hasTable('plans'));
            
        } catch (\Exception $e) {
            $this->fail('Fresh migration failed: ' . $e->getMessage());
        }
    }

    /**
     * Test de création d'un enregistrement dans chaque table critique.
     */
    public function test_can_insert_in_critical_tables(): void
    {
        $this->assertDatabaseReady();
        
        try {
            // 1. Insérer un plan
            $planId = (string) \Illuminate\Support\Str::uuid();
            DB::table('plans')->insert([
                'id' => $planId,
                'name' => 'Diagnostic Plan',
                'code' => 'diag_plan',
                'price_monthly' => 0,
                'currency_code' => 'EUR',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $this->assertDatabaseHas('plans', ['id' => $planId]);
            echo "✓ Plan insertion successful\n";
            
            // 2. Insérer une company
            $companyId = (string) \Illuminate\Support\Str::uuid();
            DB::table('companies')->insert([
                'id' => $companyId,
                'name' => 'Diagnostic Company',
                'plan_id' => $planId,
                'currency_code' => 'EUR',
                'language_code' => 'fr',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $this->assertDatabaseHas('companies', ['id' => $companyId]);
            echo "✓ Company insertion successful\n";
            
            // 3. Insérer un user
            $userId = (string) \Illuminate\Support\Str::uuid();
            DB::table('users')->insert([
                'id' => $userId,
                'first_name' => 'Diagnostic',
                'last_name' => 'User',
                'email' => 'diagnostic@example.com',
                'password' => bcrypt('password'),
                'company_id' => $companyId,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $this->assertDatabaseHas('users', ['id' => $userId]);
            echo "✓ User insertion successful\n";
            
        } catch (\Exception $e) {
            $this->fail('Failed to insert in critical tables: ' . $e->getMessage());
        }
    }

    /**
     * Test des compteurs de tables.
     */
    public function test_table_counts(): void
    {
        $this->assertDatabaseReady();
        
        $tables = ['users', 'companies', 'plans', 'features', 'roles'];
        
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                echo "Table '$table': $count records\n";
                $this->assertGreaterThanOrEqual(0, $count);
            } else {
                $this->fail("Table '$table' does not exist");
            }
        }
    }
}
