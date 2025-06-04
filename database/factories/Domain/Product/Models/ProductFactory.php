<?php

namespace Database\Factories\Domain\Product\Models;

use App\Domain\Product\Models\Product;
use App\Domain\Company\Models\Company;
use App\Domain\Product\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Product\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'category_id' => null,
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->optional()->paragraph(),
            'reference' => $this->faker->optional()->unique()->regexify('[A-Z]{3}-[0-9]{3}'),
            'unit_price' => $this->faker->randomFloat(2, 5, 1000),
            'cost_price' => $this->faker->optional()->randomFloat(2, 2, 500),
            'vat_rate' => $this->faker->randomElement([0, 5.5, 10, 20]),
            'unit' => $this->faker->optional()->randomElement(['piece', 'kg', 'liter', 'meter', 'box']),
            'weight' => $this->faker->optional()->randomFloat(2, 0.1, 50),
            'dimensions' => $this->faker->optional()->regexify('[0-9]{1,2}x[0-9]{1,2}x[0-9]{1,2}'),
            'barcode' => $this->faker->optional()->ean13(),
            'stock_quantity' => $this->faker->optional()->numberBetween(0, 500),
            'stock_alert_threshold' => $this->faker->optional()->numberBetween(5, 50),
            'is_active' => $this->faker->boolean(85),
            'attributes' => $this->faker->optional()->passthrough([
                'color' => $this->faker->colorName(),
                'size' => $this->faker->randomElement(['XS', 'S', 'M', 'L', 'XL']),
                'material' => $this->faker->randomElement(['Cotton', 'Polyester', 'Wool', 'Silk'])
            ]),
            'variants' => $this->faker->optional()->passthrough([
                [
                    'name' => 'color',
                    'value' => 'red',
                    'price_adjustment' => $this->faker->randomFloat(2, -10, 10)
                ],
                [
                    'name' => 'size',
                    'value' => 'large',
                    'price_adjustment' => $this->faker->randomFloat(2, 0, 5)
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
     * Indicate that the product is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the product is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a product with stock tracking.
     */
    public function withStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => $this->faker->numberBetween(10, 100),
            'stock_alert_threshold' => $this->faker->numberBetween(5, 20),
        ]);
    }

    /**
     * Create a product with variants.
     */
    public function withVariants(): static
    {
        return $this->state(fn (array $attributes) => [
            'variants' => [
                [
                    'name' => 'color',
                    'value' => 'blue',
                    'price_adjustment' => 5.00
                ],
                [
                    'name' => 'size',
                    'value' => 'large',
                    'price_adjustment' => 2.50
                ]
            ],
        ]);
    }

    /**
     * Create an expensive product.
     */
    public function expensive(): static
    {
        return $this->state(fn (array $attributes) => [
            'unit_price' => $this->faker->randomFloat(2, 500, 2000),
            'cost_price' => $this->faker->randomFloat(2, 200, 800),
        ]);
    }
}
