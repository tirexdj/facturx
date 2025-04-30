<?php

namespace Database\Seeders;

use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Product;
use Illuminate\Database\Seeder;

class QuoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Devis minimal pour chaque entreprise
        
        // Entreprise 1 (Plomberie Dupont - FREE)
        $this->createQuoteForCompany1();
        
        // Entreprise 2 (Boulangerie Martin - STARTER)
        $this->createQuoteForCompany2();
        
        // Entreprise 3 (Construction Leroy - BUSINESS)
        $this->createQuoteForCompany3();
        
        // Entreprise 4 (Tech Solutions - PREMIUM)
        $this->createQuoteForCompany4();
    }
    
    /**
     * Crée un devis pour l'entreprise 1
     */
    private function createQuoteForCompany1(): void
    {
        $product = Product::where('company_id', 1)->first();
        
        $quote = Quote::create([
            'company_id' => 1,
            'client_id' => 1,
            'number' => 'D-2025-001',
            'date' => now()->subDays(15),
            'expiration_date' => now()->addDays(15),
            'status' => 'sent',
            'sent_at' => now()->subDays(10),
            'subtotal' => 120.00,
            'discount_type' => null,
            'discount_value' => 0,
            'discount_amount' => 0,
            'tax_amount' => 24.00,
            'total' => 144.00,
            'notes' => 'Intervention pour fuite sous évier',
            'terms' => 'Paiement à réception de facture',
        ]);
        
        QuoteItem::create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
            'description' => $product->description,
            'quantity' => 2,
            'unit_price' => 60.00,
            'discount_type' => null,
            'discount_value' => 0,
            'discount_amount' => 0,
            'tax_rate_id' => $product->tax_rate_id,
            'tax_amount' => 24.00,
            'subtotal' => 120.00,
            'total' => 144.00,
        ]);
    }
    
    /**
     * Crée un devis pour l'entreprise 2
     */
    private function createQuoteForCompany2(): void
    {
        $product = Product::where('company_id', 2)->first();
        
        $quote = Quote::create([
            'company_id' => 2,
            'client_id' => 4,
            'number' => 'DEV-2025-001',
            'date' => now()->subDays(20),
            'expiration_date' => now()->addDays(10),
            'status' => 'accepted',
            'sent_at' => now()->subDays(15),
            'accepted_at' => now()->subDays(10),
            'subtotal' => 150.00,
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'discount_amount' => 15.00,
            'tax_amount' => 7.43,
            'total' => 142.43,
            'notes' => 'Fourniture quotidienne de pains',
            'terms' => 'Paiement à 30 jours',
        ]);
        
        QuoteItem::create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
            'description' => 'Baguette tradition (lot de 125)',
            'quantity' => 125,
            'unit_price' => 1.20,
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'discount_amount' => 15.00,
            'tax_rate_id' => $product->tax_rate_id,
            'tax_amount' => 7.43,
            'subtotal' => 150.00,
            'total' => 142.43,
        ]);
    }
    
    /**
     * Crée un devis pour l'entreprise 3
     */
    private function createQuoteForCompany3(): void
    {
        $product = Product::where('company_id', 3)->first();
        
        $quote = Quote::create([
            'company_id' => 3,
            'client_id' => 6,
            'number' => 'DEV-2025-001',
            'date' => now()->subDays(30),
            'expiration_date' => now()->addDays(30),
            'status' => 'draft',
            'subtotal' => 18000.00,
            'discount_type' => null,
            'discount_value' => 0,
            'discount_amount' => 0,
            'tax_amount' => 3600.00,
            'total' => 21600.00,
            'notes' => 'Construction mur porteur immeuble Bordeaux',
            'terms' => 'Acompte de 30% à la commande, solde à la livraison',
        ]);
        
        QuoteItem::create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
            'description' => 'Travaux de maçonnerie',
            'quantity' => 100,
            'unit_price' => 180.00,
            'discount_type' => null,
            'discount_value' => 0,
            'discount_amount' => 0,
            'tax_rate_id' => $product->tax_rate_id,
            'tax_amount' => 3600.00,
            'subtotal' => 18000.00,
            'total' => 21600.00,
        ]);
    }
    
    /**
     * Crée un devis pour l'entreprise 4
     */
    private function createQuoteForCompany4(): void
    {
        $product = Product::where('company_id', 4)->first();
        
        $quote = Quote::create([
            'company_id' => 4,
            'client_id' => 10,
            'number' => 'QUO-2025-001',
            'date' => now()->subDays(10),
            'expiration_date' => now()->addDays(20),
            'status' => 'sent',
            'sent_at' => now()->subDays(5),
            'subtotal' => 8000.00,
            'discount_type' => 'percentage',
            'discount_value' => 5,
            'discount_amount' => 400.00,
            'tax_amount' => 1520.00,
            'total' => 9120.00,
            'notes' => 'Développement site e-commerce',
            'terms' => '40% à la commande, 30% à mi-projet, 30% à la livraison',
        ]);
        
        QuoteItem::create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
            'description' => 'Développement web - Site e-commerce',
            'quantity' => 10,
            'unit_price' => 800.00,
            'discount_type' => 'percentage',
            'discount_value' => 5,
            'discount_amount' => 400.00,
            'tax_rate_id' => $product->tax_rate_id,
            'tax_amount' => 1520.00,
            'subtotal' => 8000.00,
            'total' => 9120.00,
        ]);
    }
}
