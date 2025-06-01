<?php

namespace Tests\Traits;

use Database\Seeders\FeatureSeeder;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RoleSeeder;

trait WithSeededDatabase
{
    /**
     * Setup pour les tests nécessitant des données de base.
     */
    protected function setUpWithSeededDatabase(): void
    {
        $this->seedEssentialData();
    }

    /**
     * Seed les données essentielles pour les tests.
     */
    protected function seedEssentialData(): void
    {
        try {
            // Exécuter les seeders essentiels dans l'ordre
            $this->seed(FeatureSeeder::class);
            $this->seed(PlanSeeder::class);
            $this->seed(RoleSeeder::class);
        } catch (\Exception $e) {
            // En cas d'erreur, créer les données manuellement
            $this->createEssentialData();
        }
    }

    /**
     * Créer les données essentielles manuellement.
     */
    protected function createEssentialData(): void
    {
        // Créer un plan de base si nécessaire
        if (!\App\Domain\Company\Models\Plan::where('code', 'free')->exists()) {
            \App\Domain\Company\Models\Plan::create([
                'name' => 'Gratuit',
                'code' => 'free',
                'description' => 'Forfait gratuit pour les tests',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'currency_code' => 'EUR',
                'is_active' => true,
                'is_public' => true,
                'trial_days' => 0,
            ]);
        }

        // Créer un rôle de base si nécessaire
        if (!\App\Domain\Auth\Models\Role::where('name', 'admin')->exists()) {
            \App\Domain\Auth\Models\Role::create([
                'name' => 'admin',
                'description' => 'Administrateur pour les tests',
                'is_system' => true,
            ]);
        }

        // Créer des features de base si nécessaire
        if (!\App\Domain\Analytics\Models\Feature::where('code', 'client_management')->exists()) {
            \App\Domain\Analytics\Models\Feature::create([
                'name' => 'Gestion des clients',
                'code' => 'client_management',
                'description' => 'Permet de gérer les clients',
                'category' => 'client',
            ]);
        }
    }
}
