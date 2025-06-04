<?php

namespace Database\Factories\Domain\Product\Models;

use App\Domain\Product\Models\Service;
use App\Domain\Company\Models\Company;
use App\Domain\Product\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Product\Models\Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'category_id' => null,
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->paragraph(),
            'unit_price' => $this->faker->randomFloat(2, 20, 500),
            'cost_price' => $this->faker->optional()->randomFloat(2, 10, 250),
            'vat_rate' => $this->faker->randomElement([0, 5.5, 10, 20]),
            'unit' => $this->faker->randomElement(['hour', 'day', 'month', 'fixed', 'piece']),
            'duration' => $this->faker->optional()->numberBetween(1, 480), // minutes
            'is_recurring' => $this->faker->boolean(30),
            'recurring_period' => $this->faker->optional()->randomElement(['week', 'month', 'quarter', 'year']),
            'setup_fee' => $this->faker->optional()->randomFloat(2, 50, 500),
            'is_active' => $this->faker->boolean(85),
            'options' => $this->faker->optional()->passthrough([
                [
                    'name' => 'Rush delivery',
                    'price' => $this->faker->randomFloat(2, 10, 100),
                    'vat_rate' => 20
                ],
                [
                    'name' => 'Extra revisions',
                    'price' => $this->faker->randomFloat(2, 25, 75),
                    'vat_rate' => 20
                ]
            ]),
        ];
    }

    /**
     * Configure the model factory to belong to a specific company.
     */
    public function forCompany(Company $company): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $company->id,
        ]);
    }

    /**
     * Configure the model factory to belong to a specific category.
     */
    public function forCategory(Category $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => $category->id,
        ]);
    }

    /**
     * Indicate that the service is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the service is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a recurring service.
     */
    public function recurring(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_recurring' => true,
            'recurring_period' => $this->faker->randomElement(['week', 'month', 'quarter', 'year']),
        ]);
    }

    /**
     * Create an hourly service.
     */
    public function hourly(): static
    {
        return $this->state(fn (array $attributes) => [
            'unit' => 'hour',
            'unit_price' => $this->faker->randomFloat(2, 25, 200),
            'duration' => $this->faker->numberBetween(60, 480), // 1-8 hours in minutes
        ]);
    }

    /**
     * Create a daily service.
     */
    public function daily(): static
    {
        return $this->state(fn (array $attributes) => [
            'unit' => 'day',
            'unit_price' => $this->faker->randomFloat(2, 200, 1500),
            'duration' => 480, // 8 hours
        ]);
    }

    /**
     * Create a fixed-price service.
     */
    public function fixed(): static
    {
        return $this->state(fn (array $attributes) => [
            'unit' => 'fixed',
            'unit_price' => $this->faker->randomFloat(2, 100, 5000),
            'duration' => null,
        ]);
    }

    /**
     * Create a service with setup fee.
     */
    public function withSetupFee(): static
    {
        return $this->state(fn (array $attributes) => [
            'setup_fee' => $this->faker->randomFloat(2, 100, 1000),
        ]);
    }

    /**
     * Create a service with options.
     */
    public function withOptions(): static
    {
        return $this->state(fn (array $attributes) => [
            'options' => [
                [
                    'name' => 'Priority support',
                    'price' => $this->faker->randomFloat(2, 50, 200),
                    'vat_rate' => 20
                ],
                [
                    'name' => 'Extended warranty',
                    'price' => $this->faker->randomFloat(2, 25, 150),
                    'vat_rate' => 20
                ],
                [
                    'name' => 'Training session',
                    'price' => $this->faker->randomFloat(2, 100, 500),
                    'vat_rate' => 20
                ]
            ],
        ]);
    }

    /**
     * Create an expensive service.
     */
    public function expensive(): static
    {
        return $this->state(fn (array $attributes) => [
            'unit_price' => $this->faker->randomFloat(2, 500, 2000),
            'cost_price' => $this->faker->randomFloat(2, 200, 800),
        ]);
    }
}
