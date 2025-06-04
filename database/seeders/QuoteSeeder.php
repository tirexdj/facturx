<?php

namespace Database\Seeders;

use App\Domain\Company\Models\Company;
use App\Domain\Customer\Models\Client;
use App\Domain\Product\Models\Product;
use App\Domain\Quote\Models\Quote;
use App\Domain\Quote\Models\QuoteItem;
use App\Enums\QuoteStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Désactiver les contraintes de clés étrangères temporairement
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $this->command->info('Seeding quotes...');

        // Obtenir des entreprises et clients existants
        $companies = Company::with('clients')->take(5)->get();
        
        if ($companies->isEmpty()) {
            $this->command->warn('No companies found. Please run CompanySeeder first.');
            return;
        }

        foreach ($companies as $company) {
            if ($company->clients->isEmpty()) {
                $this->command->warn("Company {$company->name} has no clients. Skipping...");
                continue;
            }

            $this->createQuotesForCompany($company);
        }

        // Réactiver les contraintes
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('Quotes seeded successfully!');
    }

    /**
     * Créer des devis pour une entreprise
     */
    private function createQuotesForCompany(Company $company): void
    {
        $clients = $company->clients;
        $products = Product::where('company_id', $company->id)->get();

        // Créer 15-25 devis par entreprise
        $quoteCount = fake()->numberBetween(15, 25);
        
        for ($i = 0; $i < $quoteCount; $i++) {
            $this->createQuote($company, $clients->random(), $products);
        }

        $this->command->info("Created {$quoteCount} quotes for company: {$company->name}");
    }

    /**
     * Créer un devis avec ses lignes
     */
    private function createQuote(Company $company, Client $client, $products): void
    {
        // Dates réalistes
        $quoteDate = fake()->dateTimeBetween('-6 months', 'now');
        $validUntil = (clone $quoteDate)->modify('+30 days');

        // Statut basé sur l'âge du devis
        $status = $this->getRealisticStatus($quoteDate, $validUntil);

        // Créer le devis
        $quote = Quote::create([
            'company_id' => $company->id,
            'customer_id' => $client->id,
            'quote_number' => $this->generateQuoteNumber($company),
            'quote_date' => $quoteDate,
            'valid_until' => $validUntil,
            'subject' => $this->getRandomSubject(),
            'notes' => fake()->boolean(40) ? fake()->paragraph() : null,
            'terms' => fake()->boolean(30) ? $this->getRandomTerms() : null,
            'status' => $status,
            'subtotal' => 0, // Sera calculé
            'tax_amount' => 0, // Sera calculé
            'total' => 0, // Sera calculé
            'discount_type' => fake()->boolean(20) ? fake()->randomElement(['percentage', 'fixed']) : null,
            'discount_value' => 0, // Sera défini si discount_type est set
            'shipping_amount' => fake()->boolean(15) ? fake()->randomFloat(2, 10, 100) : 0,
            'sent_at' => in_array($status, ['sent', 'pending', 'accepted', 'declined', 'expired', 'converted']) 
                ? fake()->dateTimeBetween($quoteDate, 'now') 
                : null,
        ]);

        // Définir la remise si applicable
        if ($quote->discount_type) {
            $quote->discount_value = $quote->discount_type === 'percentage' 
                ? fake()->randomFloat(1, 5, 15) 
                : fake()->randomFloat(2, 50, 200);
            $quote->save();
        }

        // Créer les lignes de devis (2-6 lignes)
        $itemCount = fake()->numberBetween(2, 6);
        $this->createQuoteItems($quote, $products, $itemCount);

        // Recalculer les totaux
        $this->recalculateTotals($quote);

        // Créer l'historique de statut
        $this->createStatusHistory($quote);
    }

    /**
     * Créer les lignes de devis
     */
    private function createQuoteItems(Quote $quote, $products, int $itemCount): void
    {
        for ($i = 0; $i < $itemCount; $i++) {
            $useProduct = !$products->isEmpty() && fake()->boolean(70);
            
            if ($useProduct) {
                $product = $products->random();
                $description = $product->name;
                $unitPrice = $product->price;
                $taxRate = $product->tax_rate;
                $productId = $product->id;
            } else {
                $description = $this->getRandomServiceDescription();
                $unitPrice = fake()->randomFloat(2, 50, 1000);
                $taxRate = fake()->randomElement([0, 5.5, 10, 20]);
                $productId = null;
            }

            $quantity = fake()->randomFloat(2, 1, 5);
            $lineTotal = $quantity * $unitPrice;

            QuoteItem::create([
                'quote_id' => $quote->id,
                'product_id' => $productId,
                'description' => $description,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'tax_rate' => $taxRate,
                'line_total' => $lineTotal,
            ]);
        }
    }

    /**
     * Obtenir un statut réaliste basé sur les dates
     */
    private function getRealisticStatus(\DateTime $quoteDate, \DateTime $validUntil): string
    {
        $now = new \DateTime();
        $daysSinceQuote = $now->diff($quoteDate)->days;
        $isExpired = $validUntil < $now;

        // Logique réaliste pour les statuts
        if ($daysSinceQuote <= 1) {
            return fake()->randomElement(['draft', 'sent']);
        } elseif ($daysSinceQuote <= 7) {
            return fake()->randomElement(['sent', 'pending']);
        } elseif ($daysSinceQuote <= 30) {
            if ($isExpired) {
                return fake()->randomElement(['expired', 'declined', 'accepted']);
            }
            return fake()->randomElement(['pending', 'accepted', 'declined']);
        } else {
            if ($isExpired) {
                return fake()->randomElement(['expired', 'declined']);
            }
            return fake()->randomElement(['accepted', 'converted', 'declined']);
        }
    }

    /**
     * Générer un numéro de devis
     */
    private function generateQuoteNumber(Company $company): string
    {
        static $counters = [];
        
        $prefix = $company->quote_prefix ?? 'DEV';
        $year = date('Y');
        
        if (!isset($counters[$company->id])) {
            $counters[$company->id] = 1;
        } else {
            $counters[$company->id]++;
        }
        
        return sprintf('%s-%s-%04d', $prefix, $year, $counters[$company->id]);
    }

    /**
     * Recalculer les totaux du devis
     */
    private function recalculateTotals(Quote $quote): void
    {
        $items = $quote->items;
        
        $subtotal = $items->sum('line_total');
        
        // Appliquer la remise
        $discountAmount = 0;
        if ($quote->discount_type === 'percentage') {
            $discountAmount = $subtotal * ($quote->discount_value / 100);
        } elseif ($quote->discount_type === 'fixed') {
            $discountAmount = $quote->discount_value;
        }
        
        $subtotalAfterDiscount = $subtotal - $discountAmount;
        
        // Calculer la TVA
        $taxAmount = $items->groupBy('tax_rate')->sum(function ($group, $rate) use ($subtotalAfterDiscount, $subtotal) {
            $groupTotal = $group->sum('line_total');
            $proportionalDiscount = $subtotal > 0 ? ($groupTotal / $subtotal) * $discountAmount : 0;
            $taxableAmount = $groupTotal - $proportionalDiscount;
            return $taxableAmount * ($rate / 100);
        });
        
        $total = $subtotalAfterDiscount + $taxAmount + $quote->shipping_amount;
        
        $quote->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
        ]);
    }

    /**
     * Créer l'historique de statut
     */
    private function createStatusHistory(Quote $quote): void
    {
        // Statut initial (création)
        $quote->statusHistories()->create([
            'status' => 'draft',
            'comment' => 'Devis créé',
            'user_id' => null,
            'created_at' => $quote->quote_date
        ]);

        // Autres statuts selon l'évolution
        if ($quote->status !== 'draft') {
            $quote->statusHistories()->create([
                'status' => $quote->status,
                'comment' => $this->getStatusComment($quote->status),
                'user_id' => null,
                'created_at' => $quote->sent_at ?? $quote->quote_date
            ]);
        }
    }

    /**
     * Obtenir des sujets de devis réalistes
     */
    private function getRandomSubject(): string
    {
        $subjects = [
            'Développement site web vitrine',
            'Application mobile iOS/Android',
            'Refonte du système informatique',
            'Formation équipe développement',
            'Audit sécurité informatique',
            'Maintenance serveurs',
            'Intégration API tierces',
            'Développement e-commerce',
            'Consulting technique',
            'Migration vers le cloud',
            'Optimisation base de données',
            'Solution de sauvegarde',
        ];

        return fake()->randomElement($subjects);
    }

    /**
     * Obtenir des descriptions de services réalistes
     */
    private function getRandomServiceDescription(): string
    {
        $services = [
            'Consultation technique - Analyse des besoins',
            'Formation utilisateurs - 1 journée',
            'Maintenance corrective',
            'Support technique - Forfait mensuel',
            'Développement sur mesure',
            'Intégration de module',
            'Tests et recette',
            'Documentation technique',
            'Déploiement en production',
            'Optimisation des performances',
        ];

        return fake()->randomElement($services);
    }

    /**
     * Obtenir des conditions générales réalistes
     */
    private function getRandomTerms(): string
    {
        return 'Conditions de paiement : 30 jours net. Devis valable 30 jours. TVA en sus selon taux en vigueur.';
    }

    /**
     * Obtenir un commentaire pour un statut
     */
    private function getStatusComment(string $status): string
    {
        return match($status) {
            'sent' => 'Devis envoyé au client',
            'pending' => 'En attente de réponse du client',
            'accepted' => 'Devis accepté par le client',
            'declined' => 'Devis refusé par le client',
            'expired' => 'Devis expiré',
            'converted' => 'Devis converti en facture',
            default => 'Mise à jour du statut'
        };
    }
}
