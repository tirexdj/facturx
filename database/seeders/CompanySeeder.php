<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Domain\Company\Models\Company;
use App\Domain\Company\Models\Plan;
use App\Domain\Shared\Models\Address;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer les plans si ils n'existent pas
        $freePlan = Plan::firstOrCreate(
            ['code' => 'free'],
            [
                'name' => 'Plan Gratuit',
                'description' => 'Plan gratuit avec fonctionnalités de base',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'currency_code' => 'EUR',
                'is_active' => true,
                'is_public' => true,
                'trial_days' => 0,
            ]
        );

        $starterPlan = Plan::firstOrCreate(
            ['code' => 'starter'],
            [
                'name' => 'Plan Starter',
                'description' => 'Plan starter pour petites entreprises',
                'price_monthly' => 9.90,
                'price_yearly' => 99.00,
                'currency_code' => 'EUR',
                'is_active' => true,
                'is_public' => true,
                'trial_days' => 30,
            ]
        );

        $businessPlan = Plan::firstOrCreate(
            ['code' => 'business'],
            [
                'name' => 'Plan Business',
                'description' => 'Plan business avec fonctionnalités avancées',
                'price_monthly' => 19.90,
                'price_yearly' => 199.00,
                'currency_code' => 'EUR',
                'is_active' => true,
                'is_public' => true,
                'trial_days' => 30,
            ]
        );

        $premiumPlan = Plan::firstOrCreate(
            ['code' => 'premium'],
            [
                'name' => 'Plan Premium',
                'description' => 'Plan premium avec toutes les fonctionnalités',
                'price_monthly' => 39.90,
                'price_yearly' => 399.00,
                'currency_code' => 'EUR',
                'is_active' => true,
                'is_public' => true,
                'trial_days' => 30,
            ]
        );

        // Entreprise 1: Forfait FREE - Artisan plombier
        $company1 = Company::create([
            'name' => 'Plomberie Dupont',
            'legal_name' => 'Plomberie Dupont',
            'siren' => '123456789',
            'siret' => '12345678900012',
            'vat_number' => 'FR12345678900',
            'website' => 'https://plomberie-dupont.fr',
            'legal_form' => 'EI', // Entreprise Individuelle
            'vat_regime' => 'franchise-en-base', // Franchise en base de TVA
            'plan_id' => $freePlan->id,
            'currency_code' => 'EUR',
            'language_code' => 'fr',
        ]);
        
        // Adresse de l'entreprise 1
        $company1->addresses()->create([
            'label' => 'Adresse principale',
            'address_line1' => '15 Rue des Artisans',
            'address_line2' => '',
            'postal_code' => '75011',
            'city' => 'Paris',
            'state_province' => 'Île-de-France',
            'country_code' => 'FR',
            'is_default' => true,
            'is_billing' => true,
            'is_shipping' => true,
        ]);
        $company1->emails()->create([
            'label' => 'Contact principal',
            'email' => 'contact@plomberie-dupont.fr',
            'is_default' => true,
            'is_verified' => true,
        ]);

        // Entreprise 2: Forfait STARTER - Boulangerie
        $company2 = Company::create([
            'name' => 'Boulangerie Martin',
            'legal_name' => 'SARL Boulangerie Martin',
            'siren' => '234567890',
            'siret' => '23456789000015',
            'vat_number' => 'FR23456789000',
            'website' => 'https://boulangerie-martin.fr',
            'legal_form' => 'SARL',
            'vat_regime' => 'reel-simplifie', // Régime simplifié
            'plan_id' => $starterPlan->id,
            'logo_path' => 'logos/boulangerie-martin.png',
            'currency_code' => 'EUR',
            'language_code' => 'fr',
        ]);
        
        // Adresse de l'entreprise 2
        $company2->addresses()->create([
            'label' => 'Adresse principale',
            'address_line1' => '8 Place du Marché',
            'address_line2' => '',
            'postal_code' => '69002',
            'city' => 'Lyon',
            'state_province' => 'Auvergne-Rhône-Alpes',
            'country_code' => 'FR',
            'is_default' => true,
            'is_billing' => true,
            'is_shipping' => true,
        ]);

        $company2->emails()->create([
            'label' => 'Contact principal',
            'email' => 'contact@boulangerie-martin.fr',
            'is_default' => true,
            'is_verified' => true,
        ]);

        // Entreprise 3: Forfait BUSINESS - Entreprise de construction
        $company3 = Company::create([
            'name' => 'Construction Leroy',
            'legal_name' => 'Leroy Construction SAS',
            'siren' => '345678901',
            'siret' => '34567890100023',
            'vat_number' => 'FR34567890100',
            'website' => 'https://construction-leroy.fr',
            'legal_form' => 'SAS',
            'vat_regime' => 'reel-normal', // Régime normal
            'plan_id' => $businessPlan->id,
            'logo_path' => 'logos/construction-leroy.png',
            'currency_code' => 'EUR',
            'language_code' => 'fr',
        ]);
        
        // Adresse principale de l'entreprise 3
        $company3->addresses()->create([
            'label' => 'Adresse principale',
            'address_line1' => '45 Avenue des Bâtisseurs',
            'address_line2' => 'Zone Industrielle Est',
            'postal_code' => '33000',
            'city' => 'Bordeaux',
            'state_province' => 'Nouvelle-Aquitaine',
            'country_code' => 'FR',
            'is_default' => true,
            'is_billing' => true,
            'is_shipping' => true,
        ]);


        
        // Adresse secondaire de l'entreprise 3
        $company3->addresses()->create([
            'label' => 'Adresse secondaire',
            'address_line1' => '12 Rue du Commerce',
            'address_line2' => '',
            'postal_code' => '31000',
            'city' => 'Toulouse',
            'state_province' => 'Occitanie',
            'country_code' => 'FR',
            'is_default' => false,
            'is_billing' => false,
            'is_shipping' => true,
        ]);

        $company3->emails()->create([
            'label' => 'Contact principal',
            'email' => 'contact@construction-leroy.fr',
            'is_default' => true,
            'is_verified' => true,
        ]);

        // Entreprise 4: Forfait PREMIUM - Entreprise technologique
        $company4 = Company::create([
            'name' => 'Tech Solutions',
            'legal_name' => 'Tech Solutions France SAS',
            'siren' => '456789012',
            'siret' => '45678901200034',
            'vat_number' => 'FR45678901200',
            'website' => 'https://tech-solutions.fr',
            'legal_form' => 'SAS',
            'vat_regime' => 'reel-normal', // Régime normal
            'plan_id' => $premiumPlan->id,
            'logo_path' => 'logos/tech-solutions.png',
            'currency_code' => 'EUR',
            'language_code' => 'fr',
        ]);
        
        // Adresse principale de l'entreprise 4
        $company4->addresses()->create([
            'label' => 'Adresse principale',
            'address_line1' => '27 Rue de l\'Innovation',
            'address_line2' => 'Bâtiment A - 3ème étage',
            'postal_code' => '59000',
            'city' => 'Lille',
            'state_province' => 'Hauts-de-France',
            'country_code' => 'FR',
            'is_default' => true,
            'is_billing' => true,
            'is_shipping' => true,
        ]);
        
        // Adresse secondaire 1 de l'entreprise 4
        $company4->addresses()->create([
            'label' => 'Bureau Paris',
            'address_line1' => '55 Boulevard Haussmann',
            'address_line2' => '7ème étage',
            'postal_code' => '75008',
            'city' => 'Paris',
            'state_province' => 'Île-de-France',
            'country_code' => 'FR',
            'is_default' => false,
            'is_billing' => false,
            'is_shipping' => true,
        ]);
        
        // Adresse secondaire 2 de l'entreprise 4
        $company4->addresses()->create([
            'label' => 'Bureau Lyon',
            'address_line1' => '42 Rue de la République',
            'address_line2' => '',
            'postal_code' => '69002',
            'city' => 'Lyon',
            'state_province' => 'Auvergne-Rhône-Alpes',
            'country_code' => 'FR',
            'is_default' => false,
            'is_billing' => false,
            'is_shipping' => true,
        ]);

        $company4->emails()->create([
            'label' => 'Contact principal',
            'email' => 'contact@tech-solutions.fr',
            'is_default' => true,
            'is_verified' => true,
        ]);
    }
}
