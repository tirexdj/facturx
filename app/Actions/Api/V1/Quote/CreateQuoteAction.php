<?php

namespace App\Actions\Api\V1\Quote;

use App\Models\Quote;
use App\Models\QuoteItem;
use App\Enums\QuoteStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateQuoteAction
{
    public function execute(array $data): Quote
    {
        return DB::transaction(function () use ($data) {
            // Créer le devis
            $quote = Quote::create([
                'company_id' => Auth::user()->company_id,
                'customer_id' => $data['customer_id'],
                'quote_number' => $this->generateQuoteNumber(),
                'quote_date' => $data['quote_date'] ?? now(),
                'valid_until' => $data['valid_until'] ?? now()->addDays(30),
                'subject' => $data['subject'] ?? null,
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
                'subtotal' => 0,
                'tax_amount' => 0,
                'total' => 0,
                'status' => QuoteStatus::DRAFT->value,
                'discount_type' => $data['discount_type'] ?? null,
                'discount_value' => $data['discount_value'] ?? 0,
                'shipping_amount' => $data['shipping_amount'] ?? 0,
            ]);

            // Ajouter les lignes de devis
            if (isset($data['items']) && is_array($data['items'])) {
                $this->createQuoteItems($quote, $data['items']);
            }

            // Recalculer les totaux
            $this->calculateTotals($quote);

            return $quote->fresh();
        });
    }

    private function generateQuoteNumber(): string
    {
        $company = Auth::user()->company;
        $prefix = $company->quote_prefix ?? 'DEV';
        $year = now()->format('Y');
        
        // Trouver le dernier numéro de devis pour cette entreprise cette année
        $lastQuote = Quote::where('company_id', $company->id)
            ->whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastQuote && preg_match('/(\d+)$/', $lastQuote->quote_number, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $year, $nextNumber);
    }

    private function createQuoteItems(Quote $quote, array $items): void
    {
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
