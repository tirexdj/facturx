<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class MigrationTest extends TestCase
{
    /**
     * Test que les migrations s'exécutent correctement.
     */
    public function test_migrations_run_successfully(): void
    {
        // Ce test vérifie que les migrations ont bien été exécutées par RefreshDatabase
        $this->assertDatabaseReady();
        
        // Vérifier un échantillon de tables importantes
        $importantTables = [
            'users', 'companies', 'plans', 'features', 'roles',
            'clients', 'products', 'services', 'quotes', 'invoices',
            'addresses', 'phone_numbers', 'emails'
        ];
        
        foreach ($importantTables as $table) {
            $this->assertTrue(
                Schema::hasTable($table), 
                "Table '$table' should exist after migrations"
            );
        }
    }

    /**
     * Test que l'on peut exécuter la migration manuellement.
     */
    public function test_can_run_migrations_manually(): void
    {
        try {
            // Tester l'exécution manuelle des migrations
            $exitCode = Artisan::call('migrate:fresh', ['--force' => true]);
            $this->assertEquals(0, $exitCode, 'Manual migration fresh should succeed');
            
            // Vérifier que les tables existent après la migration manuelle
            $this->assertDatabaseReady();
            
        } catch (\Exception $e) {
            $this->fail('Migration fresh failed: ' . $e->getMessage());
        }
    }

    /**
     * Test le statut des migrations.
     */
    public function test_migration_status(): void
    {
        try {
            $exitCode = Artisan::call('migrate:status');
            $this->assertEquals(0, $exitCode, 'Migration status command should succeed');
            
            $output = Artisan::output();
            $this->assertStringContainsString('create_facturx_table', $output);
            
        } catch (\Exception $e) {
            $this->fail('Migration status check failed: ' . $e->getMessage());
        }
    }

    /**
     * Test que la structure de la migration principale est correcte.
     */
    public function test_main_migration_structure(): void
    {
        $this->assertDatabaseReady();
        
        // Vérifier les colonnes importantes de la table users
        $userColumns = [
            'id', 'first_name', 'last_name', 'username', 'email', 'password',
            'company_id', 'role_id', 'is_active', 'created_at', 'updated_at', 'deleted_at'
        ];
        
        foreach ($userColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('users', $column),
                "Users table should have column '$column'"
            );
        }
        
        // Vérifier les colonnes importantes de la table companies
        $companyColumns = [
            'id', 'name', 'siren', 'siret', 'plan_id', 'vat_regime',
            'created_at', 'updated_at', 'deleted_at'
        ];
        
        foreach ($companyColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('companies', $column),
                "Companies table should have column '$column'"
            );
        }
    }

    /**
     * Test des contraintes de clés étrangères.
     */
    public function test_foreign_key_constraints(): void
    {
        $this->assertDatabaseReady();
        
        // Test d'insertion basique pour vérifier que les contraintes fonctionnent
        // (sans violer les contraintes)
        
        // 1. Créer un plan
        $planId = (string) \Illuminate\Support\Str::uuid();
        \Illuminate\Support\Facades\DB::table('plans')->insert([
            'id' => $planId,
            'name' => 'Test Plan',
            'code' => 'test_plan',
            'price_monthly' => 0,
            'currency_code' => 'EUR',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // 2. Créer une company avec le plan
        $companyId = (string) \Illuminate\Support\Str::uuid();
        \Illuminate\Support\Facades\DB::table('companies')->insert([
            'id' => $companyId,
            'name' => 'Test Company',
            'plan_id' => $planId,
            'currency_code' => 'EUR',
            'language_code' => 'fr',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // 3. Créer un utilisateur avec la company
        $userId = (string) \Illuminate\Support\Str::uuid();
        \Illuminate\Support\Facades\DB::table('users')->insert([
            'id' => $userId,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'company_id' => $companyId,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Vérifier que tout a été inséré correctement
        $this->assertDatabaseHas('plans', ['id' => $planId]);
        $this->assertDatabaseHas('companies', ['id' => $companyId, 'plan_id' => $planId]);
        $this->assertDatabaseHas('users', ['id' => $userId, 'company_id' => $companyId]);
    }
}
