<?php

namespace App\Actions\Api\V1\Quote;

use App\Models\Quote;
use App\Models\QuoteItem;
use App\Enums\QuoteStatus;
use Illuminate\Support\Facades\DB;

class UpdateQuoteAction
{
    public function execute(Quote $quote, array $data): Quote
    {
        return DB::transaction(function () use ($quote, $data) {
            // Vérifier que le devis peut être modifié
            if (!$this->canUpdate($quote)) {
                throw new \Exception('Ce devis ne peut plus être modifié.');
            }

            // Mettre à jour les informations du devis
            $quote->update([
                'customer_id' => $data['customer_id'] ?? $quote->customer_id,
                'quote_date' => $data['quote_date'] ?? $quote->quote_date,
                'valid_until' => $data['valid_until'] ?? $quote->valid_until,
                'subject' => $data['subject'] ?? $quote->subject,
                'notes' => $data['notes'] ?? $quote->notes,
                'terms' => $data['terms'] ?? $quote->terms,
                'discount_type' => $data['discount_type'] ?? $quote->discount_type,
                'discount_value' => $data['discount_value'] ?? $quote->discount_value,
                'shipping_amount' => $data['shipping_amount'] ?? $quote->shipping_amount,
            ]);

            // Mettre à jour les lignes de devis si fournies
            if (isset($data['items']) && is_array($data['items'])) {
                $this->updateQuoteItems($quote, $data['items']);
            }

            // Recalculer les totaux
            $this->calculateTotals($quote);

            return $quote->fresh();
        });
    }

    private function canUpdate(Quote $quote): bool
    {
        // Un devis ne peut être modifié que s'il est en brouillon ou envoyé
        return in_array($quote->status, [
            QuoteStatus::DRAFT->value,
            QuoteStatus::SENT->value,
            QuoteStatus::PENDING->value
        ]);
    }

    private function updateQuoteItems(Quote $quote, array $items): void
    {
        // Supprimer les anciennes lignes
        $quote->items()->delete();

        // Créer les nouvelles lignes
        foreach ($items as $itemData) {
            QuoteItem::create([
                'quote_id' => $quote->id,
                'product_id' => $itemData['product_id'] ?? null,
                'description' => $itemData['description'],
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'tax_rate' => $itemData['tax_rate'] ?? 20,
                'line_total' => $itemData['quantity'] * $itemData['unit_price'],
            ]);
        }
    }

    private function calculateTotals(Quote $quote): void
    {
        $items = $quote->items;
        
        $subtotal = $items->sum('line_total');
        
        // Appliquer la remise globale
        if ($quote->discount_type === 'percentage') {
            $discountAmount = $subtotal * ($quote->discount_value / 100);
        } else {
            $discountAmount = $quote->discount_value;
        }
        
        $subtotalAfterDiscount = $subtotal - $discountAmount;
        
        // Calculer la TVA par taux
        $taxAmount = $items->groupBy('tax_rate')->sum(function ($group, $rate) use ($subtotalAfterDiscount, $subtotal) {
            $groupTotal = $group->sum('line_total');
            $proportionalDiscount = $subtotal > 0 ? ($groupTotal / $subtotal) * ($subtotalAfterDiscount - $subtotal) : 0;
            $taxableAmount = $groupTotal + $proportionalDiscount;
            return $taxableAmount * ($rate / 100);
        });
        
        $total = $subtotalAfterDiscount + $taxAmount + $quote->shipping_amount;
        
        $quote->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
        ]);
    }
}
