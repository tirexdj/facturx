<?php

namespace Database\Factories\Domain\Shared\Models;

use App\Domain\Shared\Models\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Shared\Models\Address>
 */
class AddressFactory extends Factory
{
    protected $model = Address::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label' => $this->faker->randomElement(['Domicile', 'Bureau', 'Entrepôt', 'Siège social']),
            'line_1' => $this->faker->streetAddress(),
            'line_2' => $this->faker->optional(0.3)->secondaryAddress(),
            'line_3' => $this->faker->optional(0.1)->word(),
            'postal_code' => $this->faker->postcode(),
            'city' => $this->faker->city(),
            'state_province' => $this->faker->optional(0.2)->state(),
            'country_code' => 'FR',
            'is_default' => false,
            'is_billing' => false,
            'is_shipping' => false,
        ];
    }

    /**
     * Indicate that the address is a billing address.
     */
    public function billing(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_billing' => true,
            'label' => 'Facturation',
        ]);
    }

    /**
     * Indicate that the address is a shipping address.
     */
    public function shipping(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_shipping' => true,
            'label' => 'Livraison',
        ]);
    }

    /**
     * Indicate that the address is the default address.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Set the addressable morph relation for a specific model.
     */
    public function forAddressable($addressable): static
    {
        return $this->state(fn (array $attributes) => [
            'addressable_type' => get_class($addressable),
            'addressable_id' => $addressable->getKey(),
        ]);
    }
}
