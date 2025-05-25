<?php

namespace Database\Factories;

use App\Domain\Company\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Company\Models\Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

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
            'price_monthly' => $this->faker->randomFloat(2, 0, 100),
            'price_yearly' => $this->faker->randomFloat(2, 0, 1000),
            'currency_code' => 'EUR',
            'is_active' => true,
            'is_public' => true,
            'trial_days' => 0,
        ];
    }

    /**
     * Indicate that the plan is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the plan is private.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => false,
        ]);
    }

    /**
     * Free plan.
     */
    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Plan Gratuit',
            'code' => 'free',
            'price_monthly' => 0,
            'price_yearly' => 0,
            'trial_days' => 0,
        ]);
    }
}
