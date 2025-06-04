<?php

namespace Database\Factories\Domain\Product\Models;

use App\Domain\Product\Models\Category;
use App\Domain\Company\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Product\Models\Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'parent_id' => null,
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->optional()->paragraph(),
            'type' => $this->faker->randomElement(['product', 'service', 'both']),
            'sort_order' => $this->faker->numberBetween(0, 100),
            'color' => $this->faker->optional()->hexColor(),
            'icon' => $this->faker->optional()->randomElement([
                'shopping-cart', 'tools', 'laptop', 'home', 'car', 
                'health', 'education', 'entertainment', 'food', 'sports'
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
     * Configure the model factory to have a specific parent.
     */
    public function forParent(Category $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent->id,
            'company_id' => $parent->company_id,
        ]);
    }

    /**
     * Create a product category.
     */
    public function product(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'product',
        ]);
    }

    /**
     * Create a service category.
     */
    public function service(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'service',
        ]);
    }

    /**
     * Create a category for both products and services.
     */
    public function both(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'both',
        ]);
    }

    /**
     * Create a root category (no parent).
     */
    public function root(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => null,
        ]);
    }

    /**
     * Create a category with a specific color.
     */
    public function withColor(string $color): static
    {
        return $this->state(fn (array $attributes) => [
            'color' => $color,
        ]);
    }

    /**
     * Create a category with a specific icon.
     */
    public function withIcon(string $icon): static
    {
        return $this->state(fn (array $attributes) => [
            'icon' => $icon,
        ]);
    }

    /**
     * Create a category with a specific sort order.
     */
    public function withSortOrder(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'sort_order' => $order,
        ]);
    }

    /**
     * Create common predefined categories.
     */
    public function electronics(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Electronics',
            'description' => 'Electronic devices and gadgets',
            'type' => 'product',
            'color' => '#3498db',
            'icon' => 'laptop',
            'sort_order' => 1,
        ]);
    }

    public function clothing(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Clothing',
            'description' => 'Fashion and apparel',
            'type' => 'product',
            'color' => '#e74c3c',
            'icon' => 'shopping-cart',
            'sort_order' => 2,
        ]);
    }

    public function consulting(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Consulting',
            'description' => 'Professional consulting services',
            'type' => 'service',
            'color' => '#2ecc71',
            'icon' => 'tools',
            'sort_order' => 1,
        ]);
    }

    public function development(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Development',
            'description' => 'Software development services',
            'type' => 'service',
            'color' => '#9b59b6',
            'icon' => 'laptop',
            'sort_order' => 2,
        ]);
    }
}
