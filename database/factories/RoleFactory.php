<?php

namespace Database\Factories;

use App\Domain\Auth\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Auth\Models\Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'description' => $this->faker->sentence(),
            'is_system' => false,
        ];
    }

    /**
     * Indicate that the role is a system role.
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_system' => true,
        ]);
    }

    /**
     * Admin role.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Administrateur',
            'description' => 'Rôle administrateur avec tous les droits',
            'is_system' => true,
        ]);
    }

    /**
     * User role.
     */
    public function user(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Utilisateur',
            'description' => 'Rôle utilisateur standard',
            'is_system' => true,
        ]);
    }
}
