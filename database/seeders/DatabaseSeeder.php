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
        $this->call([
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
