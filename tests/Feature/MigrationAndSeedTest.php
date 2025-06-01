<?php

namespace Tests\Feature;

use Tests\TestCase;

class MigrationAndSeedTest extends TestCase
{
    /**
     * Test les migrations de base.
     */
    public function test_migrations_create_tables(): void
    {
        // S'assurer que la base est prête
        $this->assertDatabaseReady();
        
        // Vérifier quelques tables métier importantes
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('clients'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('products'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('services'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('quotes'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('invoices'));
    }

    /**
     * Test de l'exécution des seeders en mode sécurisé.
     */
    public function test_can_run_seeders_safely(): void
    {
        // S'assurer que la base est prête
        $this->assertDatabaseReady();
        
        // Activer les seeders pour ce test
        $this->seed = true;
        $this->seedEssentialData();
        
        // Vérifier que les seeders n'ont pas planté
        $this->assertTrue(true, 'Seeders executed without throwing exceptions');
    }

    /**
     * Test de création d'un utilisateur simple sans dépendances.
     */
    public function test_can_create_simple_user(): void
    {
        // S'assurer que la base est prête
        $this->assertDatabaseReady();
        
        $userData = $this->createSimpleUser();
        
        $this->assertIsArray($userData);
        $this->assertArrayHasKey('email', $userData);
        $this->assertArrayHasKey('first_name', $userData);
        $this->assertArrayHasKey('last_name', $userData);
        
        $this->assertDatabaseHas('users', [
            'email' => $userData['email']
        ]);
    }
}
