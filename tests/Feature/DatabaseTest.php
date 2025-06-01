<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class DatabaseTest extends TestCase
{
    /**
     * Test que la base de données est correctement migrée.
     */
    public function test_database_is_migrated(): void
    {
        // Vérifier que la base est correctement initialisée
        $this->assertDatabaseReady();
        
        // Vérifier que la base est vide au départ
        $this->assertDatabaseCount('users', 0);
        
        // Créer un utilisateur pour vérifier que tout fonctionne
        $userData = $this->createSimpleUser();
        
        $this->assertDatabaseHas('users', [
            'email' => $userData['email']
        ]);
        
        $this->assertDatabaseCount('users', 1);
    }

    /**
     * Test des informations de la base de données.
     */
    public function test_database_connection(): void
    {
        $dbState = $this->debugDatabaseState();
        
        $this->assertTrue($dbState['pdo_available'], 'PDO connection should be available');
        $this->assertEquals('sqlite', $dbState['connection']);
        $this->assertEquals(':memory:', $dbState['database']);
        
        // Vérifier qu'au moins quelques tables sont présentes
        $this->assertGreaterThan(5, count($dbState['tables']), 'Should have multiple tables. Current tables: ' . implode(', ', $dbState['tables']));
        $this->assertContains('users', $dbState['tables']);
        $this->assertContains('companies', $dbState['tables']);
        $this->assertContains('plans', $dbState['tables']);
    }

    /**
     * Test de la structure de la table users.
     */
    public function test_users_table_structure(): void
    {
        $this->assertDatabaseReady();
        
        // Vérifier que les colonnes importantes existent
        $this->assertTrue(Schema::hasColumn('users', 'id'));
        $this->assertTrue(Schema::hasColumn('users', 'email'));
        $this->assertTrue(Schema::hasColumn('users', 'password'));
        $this->assertTrue(Schema::hasColumn('users', 'first_name'));
        $this->assertTrue(Schema::hasColumn('users', 'last_name'));
        $this->assertTrue(Schema::hasColumn('users', 'created_at'));
        $this->assertTrue(Schema::hasColumn('users', 'updated_at'));
    }

    /**
     * Test de la structure de la table companies.
     */
    public function test_companies_table_structure(): void
    {
        $this->assertDatabaseReady();
        
        // Vérifier que les colonnes importantes existent
        $this->assertTrue(Schema::hasColumn('companies', 'id'));
        $this->assertTrue(Schema::hasColumn('companies', 'name'));
        $this->assertTrue(Schema::hasColumn('companies', 'siren'));
        $this->assertTrue(Schema::hasColumn('companies', 'siret'));
        $this->assertTrue(Schema::hasColumn('companies', 'plan_id'));
        $this->assertTrue(Schema::hasColumn('companies', 'created_at'));
        $this->assertTrue(Schema::hasColumn('companies', 'updated_at'));
    }

    /**
     * Test de création d'un utilisateur avec données valides.
     */
    public function test_create_user_with_valid_data(): void
    {
        $this->assertDatabaseReady();
        
        $userData = [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('users')->insert($userData);

        $this->assertDatabaseHas('users', [
            'email' => 'jane.smith@example.com',
            'first_name' => 'Jane',
            'last_name' => 'Smith'
        ]);
    }

    /**
     * Test de l'intégrité référentielle de base.
     */
    public function test_database_foreign_keys(): void
    {
        $this->assertDatabaseReady();
        
        // Les tables avec des clés étrangères devraient exister
        $this->assertTrue(Schema::hasTable('clients'));
        $this->assertTrue(Schema::hasTable('products'));
        $this->assertTrue(Schema::hasTable('quotes'));
        $this->assertTrue(Schema::hasTable('invoices'));
        
        // Vérifier quelques colonnes de clés étrangères importantes
        $this->assertTrue(Schema::hasColumn('users', 'company_id'));
        $this->assertTrue(Schema::hasColumn('companies', 'plan_id'));
        $this->assertTrue(Schema::hasColumn('clients', 'company_id'));
        $this->assertTrue(Schema::hasColumn('products', 'company_id'));
    }
}
