<?php

namespace Database\Factories\Domain\Customer\Models;

use App\Domain\Customer\Models\Client;
use App\Domain\Company\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Customer\Models\Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $clientType = $this->faker->randomElement(['individual', 'company']);
        
        if ($clientType === 'individual') {
            return [
                'client_type' => 'individual',
                'name' => $this->faker->lastName() . ' ' . $this->faker->firstName(),
                'legal_name' => null,
                'trading_name' => null,
                'siren' => null,
                'siret' => null,
                'vat_number' => null,
                'registration_number' => null,
                'legal_form' => null,
                'website' => null,
                'currency_code' => 'EUR',
                'language_code' => 'fr',
                'credit_limit' => null,
                'notes' => $this->faker->optional(0.3)->paragraph(),
                'tags' => $this->faker->optional(0.2)->randomElements(['VIP', 'Nouveau', 'Fidèle', 'Prospect'], 2),
            ];
        } else {
            return [
                'client_type' => 'company',
                'name' => $this->faker->company(),
                'legal_name' => $this->faker->optional(0.7)->company(),
                'trading_name' => $this->faker->optional(0.3)->company(),
                'siren' => $this->faker->optional(0.8)->siren(),
                'siret' => $this->faker->optional(0.8)->siret(),
                'vat_number' => $this->faker->optional(0.6)->vat(),
                'registration_number' => $this->faker->optional(0.5)->numerify('RCS-########'),
                'legal_form' => $this->faker->optional(0.7)->randomElement(['SARL', 'SAS', 'SA', 'EURL', 'SNC', 'Auto-entrepreneur']),
                'website' => $this->faker->optional(0.4)->url(),
                'currency_code' => 'EUR',
                'language_code' => 'fr',
                'credit_limit' => $this->faker->optional(0.3)->randomFloat(2, 1000, 50000),
                'notes' => $this->faker->optional(0.3)->paragraph(),
                'tags' => $this->faker->optional(0.2)->randomElements(['VIP', 'Nouveau', 'Fidèle', 'Prospect', 'PME', 'GE'], 2),
            ];
        }
    }

    /**
     * Indicate that the client is an individual.
     */
    public function individual(): static
    {
        return $this->state(fn (array $attributes) => [
            'client_type' => 'individual',
            'name' => $this->faker->lastName() . ' ' . $this->faker->firstName(),
            'legal_name' => null,
            'trading_name' => null,
            'siren' => null,
            'siret' => null,
            'vat_number' => null,
            'registration_number' => null,
            'legal_form' => null,
            'website' => null,
        ]);
    }

    /**
     * Indicate that the client is a company.
     */
    public function company(): static
    {
        return $this->state(fn (array $attributes) => [
            'client_type' => 'company',
            'name' => $this->faker->company(),
            'legal_name' => $this->faker->company(),
            'siren' => $this->faker->siren(),
            'siret' => $this->faker->siret(),
            'vat_number' => $this->faker->vatNumber(),
            'legal_form' => $this->faker->randomElement(['SARL', 'SAS', 'SA', 'EURL']),
        ]);
    }

    /**
     * Configure the factory to use a specific name.
     */
    public function withName(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }

    /**
     * Configure the factory to use specific type.
     */
    public function type(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'client_type' => $type,
        ]);
    }
}
