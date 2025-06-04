<?php

namespace App\Actions\Api\V1\Quote;

use App\Models\Quote;
use App\Enums\QuoteStatus;
use Illuminate\Support\Facades\DB;

class DeleteQuoteAction
{
    public function execute(Quote $quote): bool
    {
        return DB::transaction(function () use ($quote) {
            // Vérifier que le devis peut être supprimé
            if (!$this->canDelete($quote)) {
                throw new \Exception('Ce devis ne peut pas être supprimé.');
            }

            // Supprimer les lignes de devis
            $quote->items()->delete();

            // Supprimer l'historique des statuts
            $quote->statusHistories()->delete();

            // Supprimer le devis
            return $quote->delete();
        });
    }

    private function canDelete(Quote $quote): bool
    {
        // Un devis ne peut être supprimé que s'il n'est pas accepté ou converti
        return !in_array($quote->status, [
            QuoteStatus::ACCEPTED->value,
            QuoteStatus::CONVERTED->value
        ]);
    }
}
