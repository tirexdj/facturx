<?php

namespace Database\Seeders;


use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use App\Domain\Auth\Models\Role;
use App\Domain\Auth\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Domain\Company\Models\Company;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupération des rôles
        $adminRole = Role::where('name', 'Administrateur')->first();
        $userRole = Role::where('name', 'Utilisateur')->first();
        $financeRole = Role::where('name', 'Finance')->first();
        $salesRole = Role::where('name', 'Commercial')->first();
        
        // Récupération des entreprises
        $companies = Company::all();
        
        // Utilisateur admin pour la première entreprise (Plomberie Dupont)
        if ($companies->count() > 0) {
            User::create([
                'first_name' => 'Jean',
                'last_name' => 'Dupont',
                'email' => 'jean@plomberie-dupont.fr',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
                'role_id' => $adminRole?->id,
                'company_id' => $companies[0]->id,
            ]);
        }
        
        // Utilisateur standard pour la première entreprise
        if ($companies->count() > 0) {
            User::create([
                'first_name' => 'Marie',
                'last_name' => 'Dupont',
                'email' => 'marie@plomberie-dupont.fr',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
                'role_id' => $userRole?->id,
                'company_id' => $companies[0]->id,
            ]);
        }
        
        // Utilisateur admin pour la deuxième entreprise (Boulangerie Martin)
        if ($companies->count() > 1) {
            User::create([
                'first_name' => 'Pierre',
                'last_name' => 'Martin',
                'email' => 'pierre@boulangerie-martin.fr',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
                'role_id' => $adminRole?->id,
                'company_id' => $companies[1]->id,
            ]);
        }
        
        // Utilisateur admin pour la troisième entreprise (Construction Leroy)
        if ($companies->count() > 2) {
            User::create([
                'first_name' => 'Sophie',
                'last_name' => 'Leroy',
                'email' => 'sophie@construction-leroy.fr',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
                'role_id' => $adminRole?->id,
                'company_id' => $companies[2]->id,
            ]);
            
            // Utilisateur Finance pour Construction Leroy
            User::create([
                'first_name' => 'Thomas',
                'last_name' => 'Leroy',
                'email' => 'thomas@construction-leroy.fr',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
                'role_id' => $financeRole?->id,
                'company_id' => $companies[2]->id,
            ]);
        }
        
        // Utilisateur admin pour la quatrième entreprise (Tech Solutions)
        if ($companies->count() > 3) {
            User::create([
                'first_name' => 'Camille',
                'last_name' => 'Dubois',
                'email' => 'camille@tech-solutions.fr',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
                'role_id' => $adminRole?->id,
                'company_id' => $companies[3]->id,
            ]);
            
            // Utilisateurs supplémentaires pour Tech Solutions
            $roles = [$userRole, $financeRole, $salesRole];
            for ($i = 1; $i <= 5; $i++) {
                $randomRole = $roles[array_rand($roles)];
                User::create([
                    'first_name' => 'Utilisateur',
                    'last_name' => (string) $i,
                    'email' => 'user' . $i . '@tech-solutions.fr',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'remember_token' => Str::random(10),
                    'role_id' => $randomRole?->id,
                    'company_id' => $companies[3]->id,
                ]);
            }
        }
    }
}
