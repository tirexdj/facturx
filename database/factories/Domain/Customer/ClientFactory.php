<?php

namespace Database\Factories\Domain\Customer;

use App\Domain\Company\Models\Company;
use App\Domain\Customer\Models\Category;
use App\Domain\Customer\Models\Client;
use App\Domain\Invoice\Models\PaymentTerm;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Customer\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Client::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isCompany = $this->faker->boolean(80); // 80% chance d'être une entreprise
        
        $name = $isCompany 
            ? $this->faker->company()
            : $this->faker->firstName() . ' ' . $this->faker->lastName();

        return [
            'company_id' => Company::factory(),
            'client_type' => $isCompany ? 'company' : 'individual',
            'name' => $name,
            'legal_name' => $isCompany ? $name . ' ' . $this->faker->companySuffix() : null,
            'trading_name' => $isCompany ? ($this->faker->boolean(30) ? $this->faker->company() : null) : null,
            'siren' => $isCompany ? $this->faker->numerify('#########') : null,
            'siret' => $isCompany ? $this->faker->numerify('##############') : null,
            'vat_number' => $isCompany && $this->faker->boolean(60) ? 'FR' . $this->faker->numerify('##########') : null,
            'registration_number' => $isCompany && $this->faker->boolean(40) ? $this->faker->numerify('###-###-###') : null,
            'legal_form' => $isCompany ? $this->faker->randomElement(['SAS', 'SARL', 'SA', 'EURL', 'SNC', 'Auto-entrepreneur']) : null,
            'website' => $this->faker->boolean(30) ? $this->faker->url() : null,
            'category_id' => null, // Will be set by states if needed
            'currency_code' => $this->faker->randomElement(['EUR', 'USD', 'GBP']),
            'language_code' => $this->faker->randomElement(['fr', 'en', 'de', 'es']),
            'payment_terms_id' => null, // Will be set by states if needed
            'credit_limit' => $this->faker->boolean(30) ? $this->faker->randomFloat(2, 1000, 50000) : null,
            'notes' => $this->faker->boolean(20) ? $this->faker->paragraph() : null,
            'tags' => $this->faker->boolean(30) ? $this->faker->randomElements(['VIP', 'Nouveau', 'Régulier', 'International', 'PME', 'Grande entreprise'], $this->faker->numberBetween(1, 3)) : null,
        ];
    }

    /**
     * Indicate that the client is a company.
     */
    public function company(): static
    {
        return $this->state(function (array $attributes) {
            $name = $this->faker->company();
            return [
                'client_type' => 'company',
                'name' => $name,
                'legal_name' => $name . ' ' . $this->faker->companySuffix(),
                'siren' => $this->faker->numerify('#########'),
                'siret' => $this->faker->numerify('##############'),
                'vat_number' => 'FR' . $this->faker->numerify('##########'),
                'legal_form' => $this->faker->randomElement(['SAS', 'SARL', 'SA', 'EURL']),
            ];
        });
    }

    /**
     * Indicate that the client is an individual.
     */
    public function individual(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'client_type' => 'individual',
                'name' => $this->faker->firstName() . ' ' . $this->faker->lastName(),
                'legal_name' => null,
                'trading_name' => null,
                'siren' => null,
                'siret' => null,
                'vat_number' => null,
                'registration_number' => null,
                'legal_form' => null,
            ];
        });
    }

    /**
     * Indicate that the client has a category.
     */
    public function withCategory(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'category_id' => Category::factory()->client(),
            ];
        });
    }

    /**
     * Indicate that the client has payment terms.
     */
    public function withPaymentTerms(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'payment_terms_id' => PaymentTerm::factory(),
            ];
        });
    }

    /**
     * Indicate that the client has overdue invoices.
     */
    public function withOverdueInvoices(): static
    {
        return $this->afterCreating(function (Client $client) {
            // This will be implemented when we create invoice factories
        });
    }
}
