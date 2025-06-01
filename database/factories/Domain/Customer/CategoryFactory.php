<?php

namespace Database\Factories\Domain\Customer;

use App\Domain\Company\Models\Company;
use App\Domain\Customer\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Customer\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->words(2, true);
        
        return [
            'company_id' => Company::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'parent_id' => null,
            'type' => 'client',
            'description' => $this->faker->boolean(60) ? $this->faker->sentence() : null,
            'color' => $this->faker->hexColor(),
            'icon' => $this->faker->randomElement([
                'users', 'building', 'briefcase', 'star', 'tag', 'heart',
                'flag', 'bookmark', 'diamond', 'gift', 'crown', 'shield'
            ]),
            'position' => $this->faker->numberBetween(1, 100),
        ];
    }

    /**
     * Indicate that the category is for clients.
     */
    public function client(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'client',
            ];
        });
    }

    /**
     * Indicate that the category is for products.
     */
    public function product(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'product',
            ];
        });
    }

    /**
     * Indicate that the category is for services.
     */
    public function service(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'service',
            ];
        });
    }

    /**
     * Indicate that the category is for expenses.
     */
    public function expense(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'expense',
            ];
        });
    }

    /**
     * Indicate that the category has a parent.
     */
    public function withParent(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'parent_id' => Category::factory(),
            ];
        });
    }

    /**
     * Create client categories with typical names.
     */
    public function clientTypes(): static
    {
        return $this->sequence(
            ['name' => 'Clients VIP', 'slug' => 'clients-vip', 'icon' => 'crown'],
            ['name' => 'Nouveaux clients', 'slug' => 'nouveaux-clients', 'icon' => 'star'],
            ['name' => 'Clients réguliers', 'slug' => 'clients-reguliers', 'icon' => 'heart'],
            ['name' => 'Grandes entreprises', 'slug' => 'grandes-entreprises', 'icon' => 'building'],
            ['name' => 'PME', 'slug' => 'pme', 'icon' => 'briefcase'],
            ['name' => 'Particuliers', 'slug' => 'particuliers', 'icon' => 'users'],
        );
    }
}
