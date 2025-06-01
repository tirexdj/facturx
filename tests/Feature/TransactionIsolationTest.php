<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Traits\ManagesTestTransactions;

class TransactionIsolationTest extends TestCase
{
    use RefreshDatabase, ManagesTestTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpManagesTestTransactions();
    }

    protected function tearDown(): void
    {
        $this->tearDownManagesTestTransactions();
        parent::tearDown();
    }

    public function test_no_active_transactions_at_start(): void
    {
        // Chaque test doit commencer avec 0 transaction active
        $this->assertEquals(0, DB::transactionLevel(), 'Des transactions sont déjà actives au début du test');
    }

    public function test_clean_database_state(): void
    {
        // Vérifier que la base est propre au début
        if (config('database.default') === 'sqlite') {
            $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            
            // Seule la table migrations devrait exister (créée par RefreshDatabase)
            $tableNames = array_map(fn($table) => $table->name, $tables);
            
            // RefreshDatabase devrait avoir créé au moins la table migrations
            $this->assertContains('migrations', $tableNames, 'La table migrations devrait exister après RefreshDatabase');
        }
    }

    public function test_can_create_and_cleanup_transaction(): void
    {
        // Démarrer une transaction
        $this->beginCleanTransaction();
        $this->assertEquals(1, DB::transactionLevel());
        
        // Créer une table temporaire dans la transaction
        DB::statement('CREATE TABLE test_transaction (id INTEGER PRIMARY KEY, value TEXT)');
        
        // Rollback
        $this->safeRollback();
        $this->assertEquals(0, DB::transactionLevel());
        
        // Vérifier que la table n'existe plus (si SQLite en mémoire)
        if (config('database.default') === 'sqlite') {
            $this->assertFalse(DB::getSchemaBuilder()->hasTable('test_transaction'));
        }
    }

    public function test_migrations_table_not_duplicated(): void
    {
        // Ce test reproduit le problème original
        
        // Vérifier que la table migrations existe une seule fois
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('migrations'));
        
        // Essayer de la créer à nouveau devrait échouer proprement ou être géré
        try {
            // Laravel utilise des migrations, donc on ne peut pas tester directement
            // Mais on peut vérifier qu'il n'y a pas de conflit de transaction
            
            $migrationCount = DB::table('migrations')->count();
            $this->assertGreaterThanOrEqual(0, $migrationCount, 'La table migrations devrait être accessible');
            
        } catch (\Exception $e) {
            $this->fail('Erreur lors de l\'accès à la table migrations: ' . $e->getMessage());
        }
    }

    public function test_database_wipe_functionality(): void
    {
        // Créer une table de test
        DB::statement('CREATE TABLE test_wipe (id INTEGER PRIMARY KEY)');
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('test_wipe'));
        
        // Nettoyer la base
        $this->wipeDatabase();
        
        // Vérifier que la table n'existe plus
        $this->assertFalse(DB::getSchemaBuilder()->hasTable('test_wipe'));
    }

    public function test_multiple_operations_in_sequence(): void
    {
        // Test pour s'assurer que plusieurs opérations successives fonctionnent
        
        // Opération 1
        DB::statement('CREATE TABLE test_seq_1 (id INTEGER PRIMARY KEY)');
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('test_seq_1'));
        
        // Opération 2
        DB::statement('CREATE TABLE test_seq_2 (id INTEGER PRIMARY KEY)');
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('test_seq_2'));
        
        // Nettoyage
        DB::statement('DROP TABLE test_seq_1');
        DB::statement('DROP TABLE test_seq_2');
        
        $this->assertFalse(DB::getSchemaBuilder()->hasTable('test_seq_1'));
        $this->assertFalse(DB::getSchemaBuilder()->hasTable('test_seq_2'));
    }

    public function test_error_recovery(): void
    {
        // Tester la récupération après erreur
        
        try {
            // Créer une erreur intentionnelle
            DB::statement('INVALID SQL STATEMENT');
        } catch (\Exception $e) {
            // L'erreur est attendue
        }
        
        // Vérifier que la connexion fonctionne encore après l'erreur
        $result = DB::select('SELECT 1 as test');
        $this->assertEquals(1, $result[0]->test);
        
        // Vérifier que les transactions fonctionnent encore
        $this->assertEquals(0, DB::transactionLevel());
    }

    public function test_debug_database_state(): void
    {
        // Utiliser la méthode de debug pour vérifier l'état
        $state = $this->debugDatabaseState();
        
        $this->assertIsArray($state);
        $this->assertArrayHasKey('connection', $state);
        $this->assertArrayHasKey('transaction_level', $state);
        $this->assertArrayHasKey('pdo_available', $state);
        
        $this->assertEquals('sqlite', $state['connection']);
        $this->assertEquals(0, $state['transaction_level']);
        $this->assertTrue($state['pdo_available']);
    }
}
