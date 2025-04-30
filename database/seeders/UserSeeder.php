<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Création d'utilisateurs admin pour chaque type de forfait
        
        // FORFAIT GRATUIT
        User::create([
            'name' => 'Jean Dupont',
            'email' => 'jean@plomberie-dupont.fr',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'plan' => 'free',
            'role' => 'admin',
            'company_id' => 1,
        ]);
        
        // FORFAIT STARTER
        User::create([
            'name' => 'Marie Martin',
            'email' => 'marie@boulangerie-martin.fr',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'plan' => 'starter',
            'role' => 'admin',
            'company_id' => 2,
        ]);
        
        // FORFAIT BUSINESS
        User::create([
            'name' => 'Pierre Leroy',
            'email' => 'pierre@construction-leroy.fr',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'plan' => 'business',
            'role' => 'admin',
            'company_id' => 3,
        ]);
        
        // Utilisateurs supplémentaires pour l'entreprise Business
        User::create([
            'name' => 'Sophie Leroy',
            'email' => 'sophie@construction-leroy.fr',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'plan' => 'business',
            'role' => 'user',
            'company_id' => 3,
        ]);
        
        User::create([
            'name' => 'Thomas Leroy',
            'email' => 'thomas@construction-leroy.fr',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'plan' => 'business',
            'role' => 'finance',
            'company_id' => 3,
        ]);
        
        // FORFAIT PREMIUM
        User::create([
            'name' => 'Camille Dubois',
            'email' => 'camille@tech-solutions.fr',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'plan' => 'premium',
            'role' => 'admin',
            'company_id' => 4,
        ]);
        
        // Utilisateurs supplémentaires pour l'entreprise Premium
        for ($i = 1; $i <= 5; $i++) {
            $roles = ['user', 'finance', 'sales'];
            User::create([
                'name' => 'Utilisateur ' . $i,
                'email' => 'user' . $i . '@tech-solutions.fr',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
                'plan' => 'premium',
                'role' => $roles[array_rand($roles)],
                'company_id' => 4,
            ]);
        }
    }
}
