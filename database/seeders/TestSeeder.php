<?php

namespace Database\Seeders;

use App\Domain\Analytics\Models\Feature;
use App\Domain\Analytics\Models\PlanFeature;
use App\Domain\Auth\Models\Permission;
use App\Domain\Auth\Models\Role;
use App\Domain\Company\Models\Plan;
use Illuminate\Database\Seeder;

class TestSeeder extends Seeder
{
    /**
     * Run the database seeds for testing.
     */
    public function run(): void
    {
        // Create basic permissions
        $this->createPermissions();
        
        // Create basic roles
        $this->createRoles();
        
        // Create basic plans
        $this->createPlans();
        
        // Create features
        $this->createFeatures();
        
        // Associate features with plans
        $this->associateFeatures();
    }

    /**
     * Create basic permissions.
     */
    private function createPermissions(): void
    {
        $permissions = [
            ['name' => 'Voir utilisateurs', 'key' => 'users.view', 'group' => 'users'],
            ['name' => 'Créer utilisateurs', 'key' => 'users.create', 'group' => 'users'],
            ['name' => 'Modifier utilisateurs', 'key' => 'users.edit', 'group' => 'users'],
            ['name' => 'Supprimer utilisateurs', 'key' => 'users.delete', 'group' => 'users'],
            
            ['name' => 'Voir clients', 'key' => 'clients.view', 'group' => 'clients'],
            ['name' => 'Créer clients', 'key' => 'clients.create', 'group' => 'clients'],
            ['name' => 'Modifier clients', 'key' => 'clients.edit', 'group' => 'clients'],
            ['name' => 'Supprimer clients', 'key' => 'clients.delete', 'group' => 'clients'],
            
            ['name' => 'Voir produits', 'key' => 'products.view', 'group' => 'products'],
            ['name' => 'Créer produits', 'key' => 'products.create', 'group' => 'products'],
            ['name' => 'Modifier produits', 'key' => 'products.edit', 'group' => 'products'],
            ['name' => 'Supprimer produits', 'key' => 'products.delete', 'group' => 'products'],
            
            ['name' => 'Voir factures', 'key' => 'invoices.view', 'group' => 'invoices'],
            ['name' => 'Créer factures', 'key' => 'invoices.create', 'group' => 'invoices'],
            ['name' => 'Modifier factures', 'key' => 'invoices.edit', 'group' => 'invoices'],
            ['name' => 'Supprimer factures', 'key' => 'invoices.delete', 'group' => 'invoices'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['key' => $permission['key']],
                $permission
            );
        }
    }

    /**
     * Create basic roles.
     */
    private function createRoles(): void
    {
        $adminRole = Role::firstOrCreate(
            ['name' => 'Administrateur'],
            [
                'description' => 'Rôle administrateur avec tous les droits',
                'is_system' => true,
            ]
        );

        $userRole = Role::firstOrCreate(
            ['name' => 'Utilisateur'],
            [
                'description' => 'Rôle utilisateur standard',
                'is_system' => true,
            ]
        );

        // Assign all permissions to admin role
        $allPermissions = Permission::all();
        $adminRole->permissions()->syncWithoutDetaching($allPermissions->pluck('id')->toArray());
        
        // Assign limited permissions to user role
        $userPermissions = Permission::whereIn('key', [
            'clients.view', 'clients.create', 'clients.edit',
            'products.view', 'products.create', 'products.edit',
            'invoices.view', 'invoices.create', 'invoices.edit',
        ])->get();
        $userRole->permissions()->syncWithoutDetaching($userPermissions->pluck('id')->toArray());
    }

    /**
     * Create basic plans.
     */
    private function createPlans(): void
    {
        Plan::firstOrCreate(
            ['code' => 'free'],
            [
                'name' => 'Plan Gratuit',
                'description' => 'Plan gratuit avec limitations',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'currency_code' => 'EUR',
                'is_active' => true,
                'is_public' => true,
                'trial_days' => 0,
            ]
        );

        Plan::firstOrCreate(
            ['code' => 'starter'],
            [
                'name' => 'Plan Starter',
                'description' => 'Plan de base pour petites entreprises',
                'price_monthly' => 9.90,
                'price_yearly' => 99.00,
                'currency_code' => 'EUR',
                'is_active' => true,
                'is_public' => true,
                'trial_days' => 30,
            ]
        );

        Plan::firstOrCreate(
            ['code' => 'business'],
            [
                'name' => 'Plan Business',
                'description' => 'Plan avancé pour entreprises en croissance',
                'price_monthly' => 19.90,
                'price_yearly' => 199.00,
                'currency_code' => 'EUR',
                'is_active' => true,
                'is_public' => true,
                'trial_days' => 30,
            ]
        );
    }

    /**
     * Create features.
     */
    private function createFeatures(): void
    {
        $features = [
            ['name' => 'Clients', 'code' => 'clients', 'category' => 'basic'],
            ['name' => 'Produits', 'code' => 'products', 'category' => 'basic'],
            ['name' => 'Factures', 'code' => 'invoices', 'category' => 'basic'],
            ['name' => 'Devis', 'code' => 'quotes', 'category' => 'basic'],
            ['name' => 'Utilisateurs', 'code' => 'users', 'category' => 'users'],
            ['name' => 'API', 'code' => 'api', 'category' => 'advanced'],
            ['name' => 'Intégrations', 'code' => 'integrations', 'category' => 'advanced'],
        ];

        foreach ($features as $feature) {
            Feature::firstOrCreate(
                ['code' => $feature['code']],
                $feature
            );
        }
    }

    /**
     * Associate features with plans.
     */
    private function associateFeatures(): void
    {
        $freePlan = Plan::where('code', 'free')->first();
        $starterPlan = Plan::where('code', 'starter')->first();
        $businessPlan = Plan::where('code', 'business')->first();

        // Features for free plan
        if ($freePlan) {
            $this->createPlanFeature($freePlan, 'clients', true, 50);
            $this->createPlanFeature($freePlan, 'products', true, 100);
            $this->createPlanFeature($freePlan, 'invoices', true, 50);
            $this->createPlanFeature($freePlan, 'quotes', true, 50);
            $this->createPlanFeature($freePlan, 'users', true, 1);
            $this->createPlanFeature($freePlan, 'api', false, 0);
            $this->createPlanFeature($freePlan, 'integrations', false, 0);
        }

        // Features for starter plan
        if ($starterPlan) {
            $this->createPlanFeature($starterPlan, 'clients', true, -1); // unlimited
            $this->createPlanFeature($starterPlan, 'products', true, -1);
            $this->createPlanFeature($starterPlan, 'invoices', true, 500);
            $this->createPlanFeature($starterPlan, 'quotes', true, 500);
            $this->createPlanFeature($starterPlan, 'users', true, 3);
            $this->createPlanFeature($starterPlan, 'api', false, 0);
            $this->createPlanFeature($starterPlan, 'integrations', true, 2);
        }

        // Features for business plan
        if ($businessPlan) {
            $this->createPlanFeature($businessPlan, 'clients', true, -1);
            $this->createPlanFeature($businessPlan, 'products', true, -1);
            $this->createPlanFeature($businessPlan, 'invoices', true, -1);
            $this->createPlanFeature($businessPlan, 'quotes', true, -1);
            $this->createPlanFeature($businessPlan, 'users', true, 10);
            $this->createPlanFeature($businessPlan, 'api', true, -1);
            $this->createPlanFeature($businessPlan, 'integrations', true, -1);
        }
    }

    /**
     * Create a plan feature association.
     */
    private function createPlanFeature(Plan $plan, string $featureCode, bool $enabled, int $limit): void
    {
        $feature = Feature::where('code', $featureCode)->first();
        
        if ($feature) {
            PlanFeature::firstOrCreate(
                [
                    'plan_id' => $plan->id,
                    'feature_id' => $feature->id,
                ],
                [
                    'is_enabled' => $enabled,
                    'value_limit' => $limit,
                ]
            );
        }
    }
}
