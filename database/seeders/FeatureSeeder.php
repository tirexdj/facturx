<?php

namespace Database\Seeders;

use App\Domain\Analytics\Models\Feature;
use Illuminate\Database\Seeder;

class FeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = [
            [
                'name' => 'Manage Companies',
                'code' => 'manage_companies',
                'description' => 'Allows user to manage multiple companies',
                'category' => 'access',
            ],
            [
                'name' => 'Maximum Clients',
                'code' => 'max_clients',
                'description' => 'Maximum number of clients allowed',
                'category' => 'limits',
            ],
            [
                'name' => 'Maximum Invoices',
                'code' => 'max_invoices',
                'description' => 'Maximum number of invoices per month',
                'category' => 'limits',
            ],
            [
                'name' => 'Maximum Products',
                'code' => 'max_products',
                'description' => 'Maximum number of products allowed',
                'category' => 'limits',
            ],
            [
                'name' => 'Maximum Services',
                'code' => 'max_services',
                'description' => 'Maximum number of services allowed',
                'category' => 'limits',
            ],
            [
                'name' => 'API Access',
                'code' => 'api_access',
                'description' => 'Allows access to API endpoints',
                'category' => 'features',
            ],
            [
                'name' => 'Custom Branding',
                'code' => 'custom_branding',
                'description' => 'Allows custom branding on documents',
                'category' => 'features',
            ],
            [
                'name' => 'Advanced Reports',
                'code' => 'advanced_reports',
                'description' => 'Allows access to advanced reporting features',
                'category' => 'features',
            ],
            [
                'name' => 'Multi-currency',
                'code' => 'multi_currency',
                'description' => 'Allows using multiple currencies',
                'category' => 'features',
            ],
            [
                'name' => 'Electronic Signature',
                'code' => 'electronic_signature',
                'description' => 'Allows electronic signature on documents',
                'category' => 'features',
            ],
            [
                'name' => 'Online Payments',
                'code' => 'online_payments',
                'description' => 'Allows online payment processing',
                'category' => 'integrations',
            ],
            [
                'name' => 'Accounting Integration',
                'code' => 'accounting_integration',
                'description' => 'Allows integration with accounting software',
                'category' => 'integrations',
            ],
        ];

        foreach ($features as $featureData) {
            Feature::firstOrCreate(
                ['code' => $featureData['code']],
                $featureData
            );
        }
    }
}
