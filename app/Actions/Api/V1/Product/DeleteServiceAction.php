<?php

namespace App\Actions\Api\V1\Product;

use App\Domain\Product\Models\Service;
use Illuminate\Support\Facades\DB;

class DeleteServiceAction
{
    public function execute(Service $service): bool
    {
        return DB::transaction(function () use ($service) {
            // Vérifier si le service est utilisé dans des devis ou factures
            $isUsed = $this->isServiceUsed($service);

            if ($isUsed) {
                // Si utilisé, on le désactive plutôt que de le supprimer
                $service->update(['is_active' => false]);
                
                activity()
                    ->performedOn($service)
                    ->log('Service désactivé (utilisé dans des documents)');

                return true;
            }

            // Log avant suppression
            activity()
                ->performedOn($service)
                ->withProperties(['attributes' => $service->toArray()])
                ->log('Service supprimé');

            return $service->delete();
        });
    }

    private function isServiceUsed(Service $service): bool
    {
        // Vérifier dans les lignes de devis
        $usedInQuotes = DB::table('quote_items')
            ->where('product_type', 'service')
            ->where('product_id', $service->id)
            ->exists();

        // Vérifier dans les lignes de factures
        $usedInInvoices = DB::table('invoice_items')
            ->where('product_type', 'service')
            ->where('product_id', $service->id)
            ->exists();

        return $usedInQuotes || $usedInInvoices;
    }
}
