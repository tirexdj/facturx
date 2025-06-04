<?php

namespace App\Actions\Api\V1\Product;

use App\Domain\Product\Models\Product;
use Illuminate\Support\Facades\DB;

class DeleteProductAction
{
    public function execute(Product $product): bool
    {
        return DB::transaction(function () use ($product) {
            // Vérifier si le produit est utilisé dans des devis ou factures
            $isUsed = $this->isProductUsed($product);

            if ($isUsed) {
                // Si utilisé, on le désactive plutôt que de le supprimer
                $product->update(['is_active' => false]);
                
                activity()
                    ->performedOn($product)
                    ->log('Produit désactivé (utilisé dans des documents)');

                return true;
            }

            // Log avant suppression
            activity()
                ->performedOn($product)
                ->withProperties(['attributes' => $product->toArray()])
                ->log('Produit supprimé');

            return $product->delete();
        });
    }

    private function isProductUsed(Product $product): bool
    {
        // Vérifier dans les lignes de devis
        $usedInQuotes = DB::table('quote_items')
            ->where('product_type', 'product')
            ->where('product_id', $product->id)
            ->exists();

        // Vérifier dans les lignes de factures
        $usedInInvoices = DB::table('invoice_items')
            ->where('product_type', 'product')
            ->where('product_id', $product->id)
            ->exists();

        return $usedInQuotes || $usedInInvoices;
    }
}
