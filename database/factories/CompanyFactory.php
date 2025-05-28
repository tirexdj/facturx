<?php

namespace Database\Factories;

use App\Domain\Company\Models\Company;
use App\Domain\Company\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Company\Models\Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'legal_name' => $this->faker->company() . ' SARL',
            'trading_name' => $this->faker->optional()->company(),
            'siren' => $this->faker->unique()->numerify('#########'),
            'siret' => $this->faker->unique()->numerify('##############'),
            'vat_number' => 'FR' . $this->faker->numerify('###########'),
            'registration_number' => $this->faker->optional()->numerify('RCS #########'),
            'legal_form' => $this->faker->randomElement(['SARL', 'SAS', 'SA', 'EI', 'EURL']),
            'website' => $this->faker->optional()->url(),
            'logo_path' => null,
            'plan_id' => Plan::factory(),
            'pdp_id' => null,
            'vat_regime' => $this->faker->randomElement(['franchise-en-base', 'reel-simplifie', 'reel-normal']),
            'fiscal_year_start' => now()->startOfYear(),
            'currency_code' => 'EUR',
            'language_code' => 'fr',
            'is_active' => true,
            'trial_ends_at' => now()->addDays(30),
        ];
    }

    /**
     * Indicate that the company has a specific plan.
     */
    public function withPlan(Plan $plan): static
    {
        return $this->state(fn (array $attributes) => [
            'plan_id' => $plan->id,
        ]);
    }

    /**
     * Indicate that the company is a micro-enterprise.
     */
    public function microEnterprise(): static
    {
        return $this->state(fn (array $attributes) => [
            'legal_form' => 'EI',
            'vat_regime' => 'franchise-en-base',
        ]);
    }

    /**
     * Indicate that the company is a SARL.
     */
    public function sarl(): static
    {
        return $this->state(fn (array $attributes) => [
            'legal_form' => 'SARL',
            'vat_regime' => 'reel-simplifie',
        ]);
    }

    /**
     * Indicate that the company is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the company trial has expired.
     */
    public function expiredTrial(): static
    {
        return $this->state(fn (array $attributes) => [
            'trial_ends_at' => now()->subDays(1),
        ]);
    }
}
