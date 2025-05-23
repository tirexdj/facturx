<?php

namespace Database\Seeders;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use App\Domain\Auth\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Création des rôles de base
        $adminRole = Role::create([
            'id' => Str::uuid(),
            'name' => 'Administrateur',
            'description' => 'Accès complet à toutes les fonctionnalités',
            'is_system' => true,
        ]);
        
        $userRole = Role::create([
            'id' => Str::uuid(),
            'name' => 'Utilisateur',
            'description' => 'Accès limité aux fonctionnalités de base',
            'is_system' => true,
        ]);
        
        $financeRole = Role::create([
            'id' => Str::uuid(),
            'name' => 'Finance',
            'description' => 'Accès aux fonctionnalités financières',
            'is_system' => true,
        ]);
        
        $salesRole = Role::create([
            'id' => Str::uuid(),
            'name' => 'Commercial',
            'description' => 'Accès aux fonctionnalités de vente',
            'is_system' => true,
        ]);
    }
}
