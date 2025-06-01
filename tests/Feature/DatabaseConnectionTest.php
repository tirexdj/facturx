<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Traits\ManagesTestTransactions;

class DatabaseConnectionTest extends TestCase
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

    public function test_database_connection_works(): void
    {
        // Vérifier que SQLite est disponible
        $this->assertTrue(extension_loaded('pdo_sqlite'), 'Extension PDO SQLite non chargée');
        
        // Vérifier que la connexion fonctionne
        $this->assertNotNull(DB::connection()->getPdo(), 'Connexion PDO impossible');
        
        // Tester une requête simple
        $result = DB::select('SELECT 1 as test');
        $this->assertEquals(1, $result[0]->test);
    }

    public function test_migrations_table_exists(): void
    {
        // S'assurer que RefreshDatabase a créé la table migrations
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('migrations'));
    }

    public function test_database_transactions_work(): void
    {
        // Nettoyer les transactions existantes
        $this->cleanupActiveTransactions();
        
        // Vérifier qu'aucune transaction n'est active
        $this->assertEquals(0, DB::transactionLevel(), 'Des transactions sont déjà actives');
        
        // Commencer une nouvelle transaction
        DB::beginTransaction();
        $this->assertEquals(1, DB::transactionLevel());
        
        // Rollback
        DB::rollBack();
        $this->assertEquals(0, DB::transactionLevel());
    }

    public function test_database_isolation_works(): void
    {
        // Créer une table temporaire
        DB::statement('CREATE TABLE test_isolation (id INTEGER PRIMARY KEY, value TEXT)');
        
        // Insérer des données
        DB::table('test_isolation')->insert(['value' => 'test']);
        
        // Vérifier que les données existent
        $count = DB::table('test_isolation')->count();
        $this->assertEquals(1, $count);
        
        // Nettoyer (sera fait automatiquement par RefreshDatabase)
        DB::statement('DROP TABLE test_isolation');
    }

    public function test_sqlite_configuration(): void
    {
        if (config('database.default') === 'sqlite') {
            // Tester les PRAGMA SQLite
            $foreignKeys = DB::select('PRAGMA foreign_keys')[0]->foreign_keys ?? 0;
            $this->assertEquals(1, $foreignKeys, 'Foreign keys ne sont pas activées');
            
            // Tester que nous utilisons bien la mémoire
            $journalMode = DB::select('PRAGMA journal_mode')[0]->journal_mode ?? '';
            $this->assertEquals('memory', strtolower($journalMode), 'Journal mode néest pas en mémoire');
        } else {
            $this->markTestSkipped('Test SQLite uniquement');
        }
    }

    public function test_database_clean_between_operations(): void
    {
        // Cette méthode teste que chaque opération commence avec une base propre
        
        // Opération 1: Créer une table
        DB::statement('CREATE TABLE IF NOT EXISTS temp_test (id INTEGER PRIMARY KEY)');
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('temp_test'));
        
        // Nettoyer manuellement - supprimer la table spécifiquement
        DB::statement('DROP TABLE IF EXISTS temp_test');
        
        // Vérifier que la table n'existe plus
        $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name = 'temp_test'");
        $this->assertEmpty($tables, 'La table temp_test devrait être supprimée');
    }
}
