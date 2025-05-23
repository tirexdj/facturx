<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanyAddress;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Entreprise 1: Forfait FREE - Artisan plombier
        $company1 = Company::create([
            'name' => 'Plomberie Dupont',
            'legal_name' => 'Plomberie Dupont',
            'siren' => '123456789',
            'siret' => '12345678900012',
            'vat_number' => 'FR12345678900',
            'email' => 'contact@plomberie-dupont.fr',
            'phone' => '01 23 45 67 89',
            'website' => 'https://plomberie-dupont.fr',
            'legal_status' => 'EI', // Entreprise Individuelle
            'capital' => null,
            'activity_code' => '4322A', // Code NAF Plomberie
            'creation_date' => '2018-05-10',
            'tax_regime' => 'franchise-en-base', // Franchise en base de TVA
            'plan' => 'free',
            'subscription_start_date' => now()->subMonths(2),
            'subscription_end_date' => null,
            'logo' => null,
        ]);
        
        // Adresse de l'entreprise 1
        CompanyAddress::create([
            'company_id' => $company1->id,
            'type' => 'main',
            'address_line_1' => '15 Rue des Artisans',
            'address_line_2' => '',
            'postal_code' => '75011',
            'city' => 'Paris',
            'state' => 'Île-de-France',
            'country' => 'FR',
            'is_default' => true,
        ]);

        // Entreprise 2: Forfait STARTER - Boulangerie
        $company2 = Company::create([
            'name' => 'Boulangerie Martin',
            'legal_name' => 'SARL Boulangerie Martin',
            'siren' => '234567890',
            'siret' => '23456789000015',
            'vat_number' => 'FR23456789000',
            'email' => 'contact@boulangerie-martin.fr',
            'phone' => '02 34 56 78 90',
            'website' => 'https://boulangerie-martin.fr',
            'legal_status' => 'SARL', // SARL
            'capital' => 5000,
            'activity_code' => '1071C', // Boulangerie-pâtisserie
            'creation_date' => '2015-03-22',
            'tax_regime' => 'reel-simplifie', // Régime simplifié
            'plan' => 'starter',
            'subscription_start_date' => now()->subMonths(6),
            'subscription_end_date' => now()->addMonths(6),
            'logo' => 'logos/boulangerie-martin.png',
        ]);
        
        // Adresse de l'entreprise 2
        CompanyAddress::create([
            'company_id' => $company2->id,
            'type' => 'main',
            'address_line_1' => '8 Place du Marché',
            'address_line_2' => '',
            'postal_code' => '69002',
            'city' => 'Lyon',
            'state' => 'Auvergne-Rhône-Alpes',
            'country' => 'FR',
            'is_default' => true,
        ]);

        // Entreprise 3: Forfait BUSINESS - Entreprise de construction
        $company3 = Company::create([
            'name' => 'Construction Leroy',
            'legal_name' => 'Leroy Construction SAS',
            'siren' => '345678901',
            'siret' => '34567890100023',
            'vat_number' => 'FR34567890100',
            'email' => 'contact@construction-leroy.fr',
            'phone' => '03 45 67 89 01',
            'website' => 'https://construction-leroy.fr',
            'legal_status' => 'SAS',
            'capital' => 50000,
            'activity_code' => '4120B', // Construction de bâtiments
            'creation_date' => '2010-11-05',
            'tax_regime' => 'reel-normal', // Régime normal
            'plan' => 'business',
            'subscription_start_date' => now()->subMonths(3),
            'subscription_end_date' => now()->addMonths(9),
            'logo' => 'logos/construction-leroy.png',
        ]);
        
        // Adresse principale de l'entreprise 3
        CompanyAddress::create([
            'company_id' => $company3->id,
            'type' => 'main',
            'address_line_1' => '45 Avenue des Bâtisseurs',
            'address_line_2' => 'Zone Industrielle Est',
            'postal_code' => '33000',
            'city' => 'Bordeaux',
            'state' => 'Nouvelle-Aquitaine',
            'country' => 'FR',
            'is_default' => true,
        ]);
        
        // Adresse secondaire de l'entreprise 3
        CompanyAddress::create([
            'company_id' => $company3->id,
            'type' => 'secondary',
            'address_line_1' => '12 Rue du Commerce',
            'address_line_2' => '',
            'postal_code' => '31000',
            'city' => 'Toulouse',
            'state' => 'Occitanie',
            'country' => 'FR',
            'is_default' => false,
        ]);

        // Entreprise 4: Forfait PREMIUM - Entreprise technologique
        $company4 = Company::create([
            'name' => 'Tech Solutions',
            'legal_name' => 'Tech Solutions France SAS',
            'siren' => '456789012',
            'siret' => '45678901200034',
            'vat_number' => 'FR45678901200',
            'email' => 'contact@tech-solutions.fr',
            'phone' => '04 56 78 90 12',
            'website' => 'https://tech-solutions.fr',
            'legal_status' => 'SAS',
            'capital' => 250000,
            'activity_code' => '6201Z', // Programmation informatique
            'creation_date' => '2008-06-15',
            'tax_regime' => 'reel-normal', // Régime normal
            'plan' => 'premium',
            'subscription_start_date' => now()->subMonths(8),
            'subscription_end_date' => now()->addMonths(16),
            'logo' => 'logos/tech-solutions.png',
        ]);
        
        // Adresse principale de l'entreprise 4
        CompanyAddress::create([
            'company_id' => $company4->id,
            'type' => 'main',
            'address_line_1' => '27 Rue de l\'Innovation',
            'address_line_2' => 'Bâtiment A - 3ème étage',
            'postal_code' => '59000',
            'city' => 'Lille',
            'state' => 'Hauts-de-France',
            'country' => 'FR',
            'is_default' => true,
        ]);
        
        // Adresse secondaire 1 de l'entreprise 4
        CompanyAddress::create([
            'company_id' => $company4->id,
            'type' => 'secondary',
            'address_line_1' => '55 Boulevard Haussmann',
            'address_line_2' => '7ème étage',
            'postal_code' => '75008',
            'city' => 'Paris',
            'state' => 'Île-de-France',
            'country' => 'FR',
            'is_default' => false,
        ]);
        
        // Adresse secondaire 2 de l'entreprise 4
        CompanyAddress::create([
            'company_id' => $company4->id,
            'type' => 'secondary',
            'address_line_1' => '42 Rue de la République',
            'address_line_2' => '',
            'postal_code' => '69002',
            'city' => 'Lyon',
            'state' => 'Auvergne-Rhône-Alpes',
            'country' => 'FR',
            'is_default' => false,
        ]);
    }
}
