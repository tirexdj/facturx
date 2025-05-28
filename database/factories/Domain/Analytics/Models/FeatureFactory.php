<?php

namespace Database\Factories\Domain\Analytics\Models;

use App\Domain\Analytics\Models\Feature;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Analytics\Models\Feature>
 */
class FeatureFactory extends Factory
{
    protected $model = Feature::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'code' => $this->faker->unique()->slug(2),
            'description' => $this->faker->sentence(),
            'category' => $this->faker->randomElement(['access', 'limits', 'features', 'integrations']),
        ];
    }

    /**
     * Feature for managing companies.
     */
    public function manageCompanies(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Manage Companies',
            'code' => 'manage_companies',
            'description' => 'Allows user to manage multiple companies',
            'category' => 'access',
        ]);
    }

    /**
     * Feature for client limits.
     */
    public function maxClients(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Maximum Clients',
            'code' => 'max_clients',
            'description' => 'Maximum number of clients allowed',
            'category' => 'limits',
        ]);
    }

    /**
     * Feature for invoice limits.
     */
    public function maxInvoices(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Maximum Invoices',
            'code' => 'max_invoices',
            'description' => 'Maximum number of invoices per month',
            'category' => 'limits',
        ]);
    }

    /**
     * Feature for product limits.
     */
    public function maxProducts(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Maximum Products',
            'code' => 'max_products',
            'description' => 'Maximum number of products allowed',
            'category' => 'limits',
        ]);
    }
}
