<?php

namespace Database\Seeders;

use App\Domain\Company\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Forfait Gratuit
        $freePlan = Plan::create([
            'id' => Str::uuid(),
            'name' => 'Gratuit',
            'code' => 'free',
            'description' => 'Forfait gratuit pour les petites entreprises',
            'price_monthly' => 0,
            'price_yearly' => 0,
            'currency_code' => 'EUR',
            'is_active' => true,
            'is_public' => true,
            'trial_days' => 0,
        ]);

        // Forfait Starter
        $starterPlan = Plan::create([
            'id' => Str::uuid(),
            'name' => 'Starter',
            'code' => 'starter',
            'description' => 'Forfait pour les PME en développement',
            'price_monthly' => 9.90,
            'price_yearly' => 99,
            'currency_code' => 'EUR',
            'is_active' => true,
            'is_public' => true,
            'trial_days' => 14,
        ]);

        // Forfait Business
        $businessPlan = Plan::create([
            'id' => Str::uuid(),
            'name' => 'Business',
            'code' => 'business',
            'description' => 'Forfait avancé pour les entreprises',
            'price_monthly' => 19.90,
            'price_yearly' => 199,
            'currency_code' => 'EUR',
            'is_active' => true,
            'is_public' => true,
            'trial_days' => 14,
        ]);

        // Forfait Premium
        $premiumPlan = Plan::create([
            'id' => Str::uuid(),
            'name' => 'Premium',
            'code' => 'premium',
            'description' => 'Forfait professionnel avancé avec toutes les fonctionnalités',
            'price_monthly' => 39.90,
            'price_yearly' => 399,
            'currency_code' => 'EUR',
            'is_active' => true,
            'is_public' => true,
            'trial_days' => 14,
        ]);
    }
}
