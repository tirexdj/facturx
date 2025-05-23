<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ClientAddress;
use App\Models\ClientContact;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clients pour l'entreprise 1 (Plomberie Dupont - FREE)
        // Maximum 50 clients pour le forfait gratuit, mais nous en créerons juste quelques-uns
        
        // Client 1 - Particulier
        $client1 = Client::create([
            'company_id' => 1,
            'type' => 'individual',
            'first_name' => 'Robert',
            'last_name' => 'Petit',
            'email' => 'robert.petit@example.com',
            'phone' => '06 12 34 56 78',
            'notes' => 'Client régulier, préfère être contacté en soirée.',
            'category' => 'regular',
            'created_at' => now()->subMonths(10),
        ]);
        
        ClientAddress::create([
            'client_id' => $client1->id,
            'type' => 'main',
            'address_line_1' => '5 Rue Victor Hugo',
            'address_line_2' => 'Appartement 4B',
            'postal_code' => '75015',
            'city' => 'Paris',
            'state' => 'Île-de-France',
            'country' => 'FR',
            'is_default' => true,
        ]);
        
        // Client 2 - Particulier
        $client2 = Client::create([
            'company_id' => 1,
            'type' => 'individual',
            'first_name' => 'Jeanne',
            'last_name' => 'Moreau',
            'email' => 'jeanne.moreau@example.com',
            'phone' => '06 23 45 67 89',
            'notes' => 'Nouvelle cliente, immeuble ancien avec problèmes de plomberie récurrents.',
            'category' => 'new',
            'created_at' => now()->subDays(45),
        ]);
        
        ClientAddress::create([
            'client_id' => $client2->id,
            'type' => 'main',
            'address_line_1' => '8 Rue du Faubourg Saint-Martin',
            'address_line_2' => '',
            'postal_code' => '75010',
            'city' => 'Paris',
            'state' => 'Île-de-France',
            'country' => 'FR',
            'is_default' => true,
        ]);
        
        // Client 3 - Professionnel (Syndic)
        $client3 = Client::create([
            'company_id' => 1,
            'type' => 'company',
            'company_name' => 'Syndic de copropriété Paris Est',
            'company_legal_name' => 'Paris Est Gestion SAS',
            'company_registration_number' => '513456789',
            'company_tax_number' => 'FR67513456789',
            'email' => 'contact@parisestgestion.fr',
            'phone' => '01 39 45 67 89',
            'website' => 'https://parisestgestion.fr',
            'notes' => 'Gestionnaire de plusieurs immeubles dans le quartier. Contact principal: Mme Bernard.',
            'category' => 'key_account',
            'created_at' => now()->subMonths(6),
        ]);
        
        ClientAddress::create([
            'client_id' => $client3->id,
            'type' => 'billing',
            'address_line_1' => '24 Avenue Parmentier',
            'address_line_2' => 'Bâtiment C',
            'postal_code' => '75011',
            'city' => 'Paris',
            'state' => 'Île-de-France',
            'country' => 'FR',
            'is_default' => true,
        ]);
        
        ClientContact::create([
            'client_id' => $client3->id,
            'first_name' => 'Sylvie',
            'last_name' => 'Bernard',
            'position' => 'Gestionnaire',
            'email' => 's.bernard@parisestgestion.fr',
            'phone' => '01 39 45 67 80',
            'mobile' => '06 72 83 94 05',
            'is_primary' => true,
        ]);
        
        // Clients pour l'entreprise 2 (Boulangerie Martin - STARTER)
        
        // Client 1 - Professionnel (Hôtel)
        $client4 = Client::create([
            'company_id' => 2,
            'type' => 'company',
            'company_name' => 'Hôtel Le Lumière',
            'company_legal_name' => 'Lumière Hôtellerie SARL',
            'company_registration_number' => '428765432',
            'company_tax_number' => 'FR31428765432',
            'email' => 'reservation@hotel-lumiere.com',
            'phone' => '04 72 56 78 90',
            'website' => 'https://hotel-lumiere.com',
            'notes' => 'Commandes quotidiennes de viennoiseries et pain pour le petit-déjeuner.',
            'category' => 'key_account',
            'created_at' => now()->subYears(1),
        ]);
        
        ClientAddress::create([
            'client_id' => $client4->id,
            'type' => 'main',
            'address_line_1' => '15 Rue de la République',
            'address_line_2' => '',
            'postal_code' => '69002',
            'city' => 'Lyon',
            'state' => 'Auvergne-Rhône-Alpes',
            'country' => 'FR',
            'is_default' => true,
        ]);
        
        ClientContact::create([
            'client_id' => $client4->id,
            'first_name' => 'Marc',
            'last_name' => 'Durand',
            'position' => 'Directeur F&B',
            'email' => 'm.durand@hotel-lumiere.com',
            'phone' => '04 72 56 78 91',
            'mobile' => '06 34 56 78 90',
            'is_primary' => true,
        ]);
        
        // Client 2 - Professionnel (Restaurant)
        $client5 = Client::create([
            'company_id' => 2,
            'type' => 'company',
            'company_name' => 'Bistrot des Amis',
            'company_legal_name' => 'Les Amis SARL',
            'company_registration_number' => '493827165',
            'company_tax_number' => 'FR23493827165',
            'email' => 'contact@bistrotdesamis.fr',
            'phone' => '04 78 12 34 56',
            'website' => 'https://bistrotdesamis.fr',
            'notes' => 'Commande du pain spécial pour les sandwichs du midi.',
            'category' => 'regular',
            'created_at' => now()->subMonths(8),
        ]);
        
        ClientAddress::create([
            'client_id' => $client5->id,
            'type' => 'main',
            'address_line_1' => '56 Rue Mercière',
            'address_line_2' => '',
            'postal_code' => '69002',
            'city' => 'Lyon',
            'state' => 'Auvergne-Rhône-Alpes',
            'country' => 'FR',
            'is_default' => true,
        ]);
        
        ClientContact::create([
            'client_id' => $client5->id,
            'first_name' => 'Antoine',
            'last_name' => 'Leclerc',
            'position' => 'Gérant',
            'email' => 'antoine@bistrotdesamis.fr',
            'phone' => '04 78 12 34 56',
            'mobile' => '06 45 67 89 10',
            'is_primary' => true,
        ]);
        
        // Clients pour l'entreprise 3 (Construction Leroy - BUSINESS)
        
        // Client 1 - Professionnel (Promoteur immobilier)
        $client6 = Client::create([
            'company_id' => 3,
            'type' => 'company',
            'company_name' => 'Aquitaine Immobilier',
            'company_legal_name' => 'Aquitaine Immobilier Développement SA',
            'company_registration_number' => '356789012',
            'company_tax_number' => 'FR76356789012',
            'email' => 'contact@aquitaine-immobilier.com',
            'phone' => '05 56 78 90 12',
            'website' => 'https://aquitaine-immobilier.com',
            'notes' => 'Client majeur avec plusieurs projets en cours. Directeur technique: M. Fournier.',
            'category' => 'key_account',
            'payment_terms' => 45, // 45 jours fin de mois
            'created_at' => now()->subYears(2),
        ]);
        
        ClientAddress::create([
            'client_id' => $client6->id,
            'type' => 'main',
            'address_line_1' => '78 Cours de l\'Intendance',
            'address_line_2' => '',
            'postal_code' => '33000',
            'city' => 'Bordeaux',
            'state' => 'Nouvelle-Aquitaine',
            'country' => 'FR',
            'is_default' => true,
        ]);
        
        ClientContact::create([
            'client_id' => $client6->id,
            'first_name' => 'Philippe',
            'last_name' => 'Fournier',
            'position' => 'Directeur Technique',
            'email' => 'p.fournier@aquitaine-immobilier.com',
            'phone' => '05 56 78 90 15',
            'mobile' => '06 56 78 90 12',
            'is_primary' => true,
        ]);
        
        ClientContact::create([
            'client_id' => $client6->id,
            'first_name' => 'Catherine',
            'last_name' => 'Laurent',
            'position' => 'Responsable Administrative',
            'email' => 'c.laurent@aquitaine-immobilier.com',
            'phone' => '05 56 78 90 16',
            'mobile' => '06 56 78 90 13',
            'is_primary' => false,
        ]);
        
        // Client 2 - Professionnel (Collectivité)
        $client7 = Client::create([
            'company_id' => 3,
            'type' => 'company',
            'company_name' => 'Mairie de Bordeaux',
            'company_legal_name' => 'Commune de Bordeaux',
            'company_registration_number' => '213300635',
            'company_tax_number' => 'FR79213300635',
            'email' => 'services.techniques@bordeaux.fr',
            'phone' => '05 56 10 20 30',
            'website' => 'https://bordeaux.fr',
            'notes' => 'Marchés publics, procédures spécifiques à respecter.',
            'category' => 'public_sector',
            'payment_terms' => 30, // 30 jours
            'created_at' => now()->subYears(1)->subMonths(5),
        ]);
        
        ClientAddress::create([
            'client_id' => $client7->id,
            'type' => 'main',
            'address_line_1' => 'Hôtel de Ville',
            'address_line_2' => 'Place Pey Berland',
            'postal_code' => '33000',
            'city' => 'Bordeaux',
            'state' => 'Nouvelle-Aquitaine',
            'country' => 'FR',
            'is_default' => true,
        ]);
        
        ClientContact::create([
            'client_id' => $client7->id,
            'first_name' => 'François',
            'last_name' => 'Moreau',
            'position' => 'Directeur des Services Techniques',
            'email' => 'f.moreau@bordeaux.fr',
            'phone' => '05 56 10 20 40',
            'mobile' => '',
            'is_primary' => true,
        ]);
        
        // Client 3 - Professionnel (Entreprise)
        $client8 = Client::create([
            'company_id' => 3,
            'type' => 'company',
            'company_name' => 'VinExpert',
            'company_legal_name' => 'VinExpert SARL',
            'company_registration_number' => '497865432',
            'company_tax_number' => 'FR32497865432',
            'email' => 'contact@vinexpert.fr',
            'phone' => '05 57 34 56 78',
            'website' => 'https://vinexpert.fr',
            'notes' => 'Construction d\'un nouveau chai et d\'une boutique.',
            'category' => 'regular',
            'payment_terms' => 30, // 30 jours
            'created_at' => now()->subMonths(8),
        ]);
        
        ClientAddress::create([
            'client_id' => $client8->id,
            'type' => 'main',
            'address_line_1' => '45 Route des Châteaux',
            'address_line_2' => '',
            'postal_code' => '33250',
            'city' => 'Pauillac',
            'state' => 'Nouvelle-Aquitaine',
            'country' => 'FR',
            'is_default' => true,
        ]);
        
        ClientContact::create([
            'client_id' => $client8->id,
            'first_name' => 'Hélène',
            'last_name' => 'Dubois',
            'position' => 'Gérante',
            'email' => 'h.dubois@vinexpert.fr',
            'phone' => '05 57 34 56 78',
            'mobile' => '06 78 90 12 34',
            'is_primary' => true,
        ]);
        
        // Client 4 - International
        $client9 = Client::create([
            'company_id' => 3,
            'type' => 'company',
            'company_name' => 'Bau & Co',
            'company_legal_name' => 'Bauunternehmen GmbH',
            'company_registration_number' => 'DE123456789',
            'company_tax_number' => 'DE987654321',
            'email' => 'kontakt@bauundco.de',
            'phone' => '+49 30 12345678',
            'website' => 'https://bauundco.de',
            'notes' => 'Client allemand, projet d\'hôtel à Bordeaux.',
            'category' => 'international',
            'payment_terms' => 30, // 30 jours
            'created_at' => now()->subMonths(4),
        ]);
        
        ClientAddress::create([
            'client_id' => $client9->id,
            'type' => 'main',
            'address_line_1' => 'Baustrasse 45',
            'address_line_2' => '',
            'postal_code' => '10115',
            'city' => 'Berlin',
            'state' => 'Berlin',
            'country' => 'DE',
            'is_default' => true,
        ]);
        
        ClientContact::create([
            'client_id' => $client9->id,
            'first_name' => 'Klaus',
            'last_name' => 'Schmidt',
            'position' => 'Projektleiter',
            'email' => 'k.schmidt@bauundco.de',
            'phone' => '+49 30 12345679',
            'mobile' => '+49 170 1234567',
            'is_primary' => true,
        ]);
        
        // Clients pour l'entreprise 4 (Tech Solutions - PREMIUM)
        
        // Créons une variété de clients pour l'entreprise premium
        for ($i = 1; $i <= 20; $i++) {
            $type = $i <= 15 ? 'company' : 'individual';
            
            if ($type === 'company') {
                // Client professionnel
                $sectors = ['tech', 'finance', 'retail', 'healthcare', 'education'];
                $sector = $sectors[array_rand($sectors)];
                
                $categories = ['key_account', 'regular', 'occasional', 'international', 'public_sector'];
                $category = $categories[array_rand($categories)];
                
                $countries = ['FR', 'DE', 'ES', 'IT', 'UK', 'BE', 'NL', 'US'];
                $country = $i <= 12 ? 'FR' : $countries[array_rand($countries)];
                
                $companyName = 'Client ' . $i . ' ' . ucfirst($sector);
                
                $client = Client::create([
                    'company_id' => 4,
                    'type' => $type,
                    'company_name' => $companyName,
                    'company_legal_name' => $companyName . ($country === 'FR' ? ' SAS' : ' Inc'),
                    'company_registration_number' => mt_rand(100000000, 999999999),
                    'company_tax_number' => $country . mt_rand(10000000000, 99999999999),
                    'email' => 'contact@' . strtolower(str_replace(' ', '', $companyName)) . '.com',
                    'phone' => '+33 ' . mt_rand(100000000, 999999999),
                    'website' => 'https://' . strtolower(str_replace(' ', '', $companyName)) . '.com',
                    'notes' => 'Notes client ' . $i,
                    'category' => $category,
                    'payment_terms' => array_rand([15, 30, 45, 60]),
                    'created_at' => now()->subMonths(mt_rand(1, 24)),
                ]);
                
                // Adresse principale
                ClientAddress::create([
                    'client_id' => $client->id,
                    'type' => 'main',
                    'address_line_1' => mt_rand(1, 100) . ' Rue ' . ['de Paris', 'de Lyon', 'de Bordeaux', 'de Marseille'][array_rand(['de Paris', 'de Lyon', 'de Bordeaux', 'de Marseille'])],
                    'address_line_2' => mt_rand(0, 1) ? 'Étage ' . mt_rand(1, 10) : '',
                    'postal_code' => mt_rand(10000, 99999),
                    'city' => ['Paris', 'Lyon', 'Bordeaux', 'Marseille', 'Toulouse'][array_rand(['Paris', 'Lyon', 'Bordeaux', 'Marseille', 'Toulouse'])],
                    'state' => '',
                    'country' => $country,
                    'is_default' => true,
                ]);
                
                // Contact principal
                ClientContact::create([
                    'client_id' => $client->id,
                    'first_name' => ['Jean', 'Marie', 'Pierre', 'Sophie', 'Thomas'][array_rand(['Jean', 'Marie', 'Pierre', 'Sophie', 'Thomas'])],
                    'last_name' => ['Dupont', 'Martin', 'Durand', 'Leroy', 'Moreau'][array_rand(['Dupont', 'Martin', 'Durand', 'Leroy', 'Moreau'])],
                    'position' => ['CEO', 'CTO', 'CFO', 'Directeur', 'Responsable Achats'][array_rand(['CEO', 'CTO', 'CFO', 'Directeur', 'Responsable Achats'])],
                    'email' => 'contact' . $i . '@' . strtolower(str_replace(' ', '', $companyName)) . '.com',
                    'phone' => '+33 ' . mt_rand(100000000, 999999999),
                    'mobile' => '+33 6' . mt_rand(10000000, 99999999),
                    'is_primary' => true,
                ]);
                
                // Second contact pour certains clients
                if ($i % 3 === 0) {
                    ClientContact::create([
                        'client_id' => $client->id,
                        'first_name' => ['Anne', 'Philippe', 'Caroline', 'Michel', 'Isabelle'][array_rand(['Anne', 'Philippe', 'Caroline', 'Michel', 'Isabelle'])],
                        'last_name' => ['Robert', 'Bernard', 'Petit', 'Richard', 'Simon'][array_rand(['Robert', 'Bernard', 'Petit', 'Richard', 'Simon'])],
                        'position' => ['Assistant', 'Responsable Technique', 'Comptable', 'Commercial', 'Responsable Administratif'][array_rand(['Assistant', 'Responsable Technique', 'Comptable', 'Commercial', 'Responsable Administratif'])],
                        'email' => 'assistant' . $i . '@' . strtolower(str_replace(' ', '', $companyName)) . '.com',
                        'phone' => '+33 ' . mt_rand(100000000, 999999999),
                        'mobile' => '+33 6' . mt_rand(10000000, 99999999),
                        'is_primary' => false,
                    ]);
                }
            } else {
                // Client particulier
                $firstNames = ['Alexandre', 'Sophie', 'Lucas', 'Emma', 'Gabriel'];
                $lastNames = ['Petit', 'Martin', 'Dubois', 'Bernard', 'Thomas'];
                
                $firstName = $firstNames[array_rand($firstNames)];
                $lastName = $lastNames[array_rand($lastNames)];
                
                $client = Client::create([
                    'company_id' => 4,
                    'type' => $type,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => strtolower($firstName) . '.' . strtolower($lastName) . '@example.com',
                    'phone' => '06 ' . mt_rand(10000000, 99999999),
                    'notes' => 'Client particulier ' . $i,
                    'category' => array_rand(['regular', 'occasional', 'new']),
                    'created_at' => now()->subMonths(mt_rand(1, 12)),
                ]);
                
                ClientAddress::create([
                    'client_id' => $client->id,
                    'type' => 'main',
                    'address_line_1' => mt_rand(1, 100) . ' Rue ' . ['des Fleurs', 'de la Paix', 'du Commerce', 'Victor Hugo'][array_rand(['des Fleurs', 'de la Paix', 'du Commerce', 'Victor Hugo'])],
                    'address_line_2' => mt_rand(0, 1) ? 'Appartement ' . mt_rand(1, 100) : '',
                    'postal_code' => mt_rand(10000, 99999),
                    'city' => ['Paris', 'Lyon', 'Bordeaux', 'Marseille', 'Toulouse'][array_rand(['Paris', 'Lyon', 'Bordeaux', 'Marseille', 'Toulouse'])],
                    'state' => '',
                    'country' => 'FR',
                    'is_default' => true,
                ]);
            }
        }
    }
}
