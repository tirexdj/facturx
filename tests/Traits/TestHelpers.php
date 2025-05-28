<?php

namespace Tests\Traits;

use App\Domain\Analytics\Models\Feature;
use App\Domain\Company\Models\Company;
use App\Domain\Company\Models\Plan;
use App\Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Collection;

trait TestHelpers
{
    /**
     * Create a user with a company and plan that has specific features enabled.
     *
     * @param array $features Array of feature codes to enable
     * @param array $planAttributes Additional plan attributes
     * @param array $companyAttributes Additional company attributes
     * @param array $userAttributes Additional user attributes
     * @return User
     */
    protected function createUserWithFeatures(
        array $features = ['manage_companies'],
        array $planAttributes = [],
        array $companyAttributes = [],
        array $userAttributes = []
    ): User {
        // Create or get features
        $featureModels = [];
        foreach ($features as $featureCode) {
            $featureModels[] = $this->getOrCreateFeature($featureCode);
        }

        // Create plan
        $plan = Plan::factory()->create($planAttributes);

        // Attach features to plan
        foreach ($featureModels as $feature) {
            $plan->features()->attach($feature->id, [
                'is_enabled' => true,
                'value_limit' => null
            ]);
        }

        // Create company
        $company = Company::factory()->for($plan)->create($companyAttributes);

        // Create user
        return User::factory()->for($company)->create($userAttributes);
    }

    /**
     * Create a super admin user that bypasses plan limitations.
     *
     * @param array $userAttributes Additional user attributes
     * @return User
     */
    protected function createSuperAdmin(array $userAttributes = []): User
    {
        // Create a plan with all features enabled
        $allFeatures = [
            'manage_companies',
            'max_clients',
            'max_invoices',
            'max_products',
            'max_services',
            'api_access',
            'custom_branding',
            'advanced_reports',
            'multi_currency',
            'electronic_signature',
            'online_payments',
            'accounting_integration',
        ];

        return $this->createUserWithFeatures($allFeatures, [
            'name' => 'Super Admin Plan',
            'code' => 'super_admin',
        ], [], array_merge([
            'is_super_admin' => true, // Si vous avez ce champ dans votre modèle User
        ], $userAttributes));
    }

    /**
     * Get or create a feature by code.
     *
     * @param string $featureCode
     * @return Feature
     */
    protected function getOrCreateFeature(string $featureCode): Feature
    {
        $featureData = $this->getFeatureDataByCode($featureCode);
        
        return Feature::firstOrCreate(
            ['code' => $featureCode],
            $featureData
        );
    }

    /**
     * Get feature data by code.
     *
     * @param string $code
     * @return array
     */
    protected function getFeatureDataByCode(string $code): array
    {
        $featuresData = [
            'manage_companies' => [
                'name' => 'Manage Companies',
                'description' => 'Allows user to manage multiple companies',
                'category' => 'access',
            ],
            'max_clients' => [
                'name' => 'Maximum Clients',
                'description' => 'Maximum number of clients allowed',
                'category' => 'limits',
            ],
            'max_invoices' => [
                'name' => 'Maximum Invoices',
                'description' => 'Maximum number of invoices per month',
                'category' => 'limits',
            ],
            'max_products' => [
                'name' => 'Maximum Products',
                'description' => 'Maximum number of products allowed',
                'category' => 'limits',
            ],
            'max_services' => [
                'name' => 'Maximum Services',
                'description' => 'Maximum number of services allowed',
                'category' => 'limits',
            ],
            'api_access' => [
                'name' => 'API Access',
                'description' => 'Allows access to API endpoints',
                'category' => 'features',
            ],
            'custom_branding' => [
                'name' => 'Custom Branding',
                'description' => 'Allows custom branding on documents',
                'category' => 'features',
            ],
            'advanced_reports' => [
                'name' => 'Advanced Reports',
                'description' => 'Allows access to advanced reporting features',
                'category' => 'features',
            ],
            'multi_currency' => [
                'name' => 'Multi-currency',
                'description' => 'Allows using multiple currencies',
                'category' => 'features',
            ],
            'electronic_signature' => [
                'name' => 'Electronic Signature',
                'description' => 'Allows electronic signature on documents',
                'category' => 'features',
            ],
            'online_payments' => [
                'name' => 'Online Payments',
                'description' => 'Allows online payment processing',
                'category' => 'integrations',
            ],
            'accounting_integration' => [
                'name' => 'Accounting Integration',
                'description' => 'Allows integration with accounting software',
                'category' => 'integrations',
            ],
        ];

        return array_merge($featuresData[$code] ?? [
            'name' => ucfirst(str_replace('_', ' ', $code)),
            'description' => 'Feature: ' . $code,
            'category' => 'features',
        ], ['code' => $code]);
    }
}
