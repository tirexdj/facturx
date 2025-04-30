<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductTaxRate;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Minimum viable products pour chaque entreprise
        
        // Entreprise 1 (Plomberie Dupont - FREE)
        $cat1 = ProductCategory::create([
            'company_id' => 1,
            'name' => 'Services',
            'description' => 'Services de plomberie',
            'parent_id' => null,
        ]);
        
        $tva1 = ProductTaxRate::create([
            'company_id' => 1,
            'name' => 'TVA Standard',
            'rate' => 20,
            'is_default' => true,
        ]);
        
        Product::create([
            'company_id' => 1,
            'type' => 'service',
            'name' => 'Intervention plomberie',
            'description' => 'Intervention standard à domicile',
            'reference' => 'PLOMB-001',
            'unit' => 'hour',
            'price' => 60,
            'tax_rate_id' => $tva1->id,
            'category_id' => $cat1->id,
            'active' => true,
        ]);
        
        // Entreprise 2 (Boulangerie Martin - STARTER)
        $cat2 = ProductCategory::create([
            'company_id' => 2,
            'name' => 'Pains',
            'description' => 'Tous types de pains',
            'parent_id' => null,
        ]);
        
        $tva2 = ProductTaxRate::create([
            'company_id' => 2,
            'name' => 'TVA Alimentaire',
            'rate' => 5.5,
            'is_default' => true,
        ]);
        
        Product::create([
            'company_id' => 2,
            'type' => 'product',
            'name' => 'Baguette tradition',
            'description' => 'Baguette de tradition française',
            'reference' => 'PAIN-001',
            'unit' => 'piece',
            'price' => 1.20,
            'tax_rate_id' => $tva2->id,
            'category_id' => $cat2->id,
            'active' => true,
        ]);
        
        // Entreprise 3 (Construction Leroy - BUSINESS)
        $cat3 = ProductCategory::create([
            'company_id' => 3,
            'name' => 'Construction',
            'description' => 'Services de construction',
            'parent_id' => null,
        ]);
        
        $tva3 = ProductTaxRate::create([
            'company_id' => 3,
            'name' => 'TVA Standard',
            'rate' => 20,
            'is_default' => true,
        ]);
        
        Product::create([
            'company_id' => 3,
            'type' => 'service',
            'name' => 'Travaux de maçonnerie',
            'description' => 'Travaux de maçonnerie (m²)',
            'reference' => 'CONS-001',
            'unit' => 'sqm',
            'price' => 180,
            'tax_rate_id' => $tva3->id,
            'category_id' => $cat3->id,
            'active' => true,
        ]);
        
        // Entreprise 4 (Tech Solutions - PREMIUM)
        $cat4 = ProductCategory::create([
            'company_id' => 4,
            'name' => 'Services IT',
            'description' => 'Services informatiques',
            'parent_id' => null,
        ]);
        
        $tva4 = ProductTaxRate::create([
            'company_id' => 4,
            'name' => 'TVA Standard',
            'rate' => 20,
            'is_default' => true,
        ]);
        
        Product::create([
            'company_id' => 4,
            'type' => 'service',
            'name' => 'Développement web',
            'description' => 'Développement d\'applications web',
            'reference' => 'DEV-001',
            'unit' => 'day',
            'price' => 800,
            'tax_rate_id' => $tva4->id,
            'category_id' => $cat4->id,
            'active' => true,
        ]);
    }
}
