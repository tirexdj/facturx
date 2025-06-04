<?php

namespace App\Actions\Api\V1\Product;

use App\Domain\Product\Models\Product;
use Illuminate\Support\Facades\DB;

class CreateProductAction
{
    public function execute(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = Product::create([
                'company_id' => $data['company_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'reference' => $data['reference'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'unit_price' => $data['unit_price'],
                'cost_price' => $data['cost_price'] ?? null,
                'vat_rate' => $data['vat_rate'],
                'unit' => $data['unit'] ?? null,
                'weight' => $data['weight'] ?? null,
                'dimensions' => $data['dimensions'] ?? null,
                'barcode' => $data['barcode'] ?? null,
                'stock_quantity' => $data['stock_quantity'] ?? null,
                'stock_alert_threshold' => $data['stock_alert_threshold'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'attributes' => $data['attributes'] ?? null,
                'variants' => $data['variants'] ?? null,
            ]);

            // Log de l'activité
            activity()
                ->performedOn($product)
                ->withProperties(['attributes' => $data])
                ->log('Produit créé');

            return $product;
        });
    }
}
