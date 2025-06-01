<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Ne pas exécuter les seeders automatiquement dans l'environnement de test
        if (app()->environment('testing')) {
            return;
        }
        
        $this->call([
            FeatureSeeder::class,
            PlanSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            CompanySeeder::class,
            // ProductSeeder::class,
            // ClientSeeder::class,
            // QuoteSeeder::class,
            // InvoiceSeeder::class,
            // PaymentSeeder::class,
            // SettingSeeder::class,
            // PdpConfigSeeder::class,
        ]);
    }
}
