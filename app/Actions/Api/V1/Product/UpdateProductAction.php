<?php

namespace App\Actions\Api\V1\Product;

use App\Domain\Product\Models\Product;
use Illuminate\Support\Facades\DB;

class UpdateProductAction
{
    public function execute(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $oldAttributes = $product->toArray();

            $product->update([
                'name' => $data['name'] ?? $product->name,
                'description' => $data['description'] ?? $product->description,
                'reference' => $data['reference'] ?? $product->reference,
                'category_id' => $data['category_id'] ?? $product->category_id,
                'unit_price' => $data['unit_price'] ?? $product->unit_price,
                'cost_price' => $data['cost_price'] ?? $product->cost_price,
                'vat_rate' => $data['vat_rate'] ?? $product->vat_rate,
                'unit' => $data['unit'] ?? $product->unit,
                'weight' => $data['weight'] ?? $product->weight,
                'dimensions' => $data['dimensions'] ?? $product->dimensions,
                'barcode' => $data['barcode'] ?? $product->barcode,
                'stock_quantity' => $data['stock_quantity'] ?? $product->stock_quantity,
                'stock_alert_threshold' => $data['stock_alert_threshold'] ?? $product->stock_alert_threshold,
                'is_active' => $data['is_active'] ?? $product->is_active,
                'attributes' => $data['attributes'] ?? $product->attributes,
                'variants' => $data['variants'] ?? $product->variants,
            ]);

            // Log de l'activité
            activity()
                ->performedOn($product)
                ->withProperties([
                    'old' => $oldAttributes,
                    'attributes' => $product->fresh()->toArray()
                ])
                ->log('Produit modifié');

            return $product->fresh();
        });
    }
}
