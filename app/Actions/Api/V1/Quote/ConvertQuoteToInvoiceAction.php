<?php

namespace App\Actions\Api\V1\Quote;

use App\Enums\QuoteStatus;
use App\Enums\InvoiceStatus;
use App\Domain\Quote\Models\Quote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Domain\Invoice\Models\Invoice;

class ConvertQuoteToInvoiceAction
{
    public function execute(Quote $quote): Invoice
    {
        return DB::transaction(function () use ($quote) {
            // Vérifier que le devis peut être converti
            if (!$this->canConvert($quote)) {
                throw new \Exception('Ce devis ne peut pas être converti en facture.');
            }

            // Créer la facture
            $invoice = Invoice::create([
                'company_id' => $quote->company_id,
                'customer_id' => $quote->customer_id,
                'quote_id' => $quote->id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'invoice_date' => now(),
                'due_date' => now()->addDays(30), // Échéance par défaut à 30 jours
                'subject' => $quote->subject,
                'notes' => $quote->notes,
                'terms' => $quote->terms,
                'subtotal' => $quote->subtotal,
                'tax_amount' => $quote->tax_amount,
                'total' => $quote->total,
                'status' => InvoiceStatus::DRAFT->value,
                'discount_type' => $quote->discount_type,
                'discount_value' => $quote->discount_value,
                'shipping_amount' => $quote->shipping_amount,
            ]);

            // Copier les lignes du devis vers la facture
            foreach ($quote->items as $quoteItem) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $quoteItem->product_id,
                    'description' => $quoteItem->description,
                    'quantity' => $quoteItem->quantity,
                    'unit_price' => $quoteItem->unit_price,
                    'tax_rate' => $quoteItem->tax_rate,
                    'line_total' => $quoteItem->line_total,
                ]);
            }

            // Mettre à jour le statut du devis
            $quote->update([
                'status' => QuoteStatus::CONVERTED->value
            ]);

            // Ajouter un historique de statut pour le devis
            $quote->statusHistories()->create([
                'status' => QuoteStatus::CONVERTED->value,
                'comment' => "Devis converti en facture {$invoice->invoice_number}",
                'user_id' => auth()->id(),
                'created_at' => now()
            ]);

            // Ajouter un historique de statut pour la facture
            $invoice->statusHistories()->create([
                'status' => InvoiceStatus::DRAFT->value,
                'comment' => "Facture créée à partir du devis {$quote->quote_number}",
                'user_id' => auth()->id(),
                'created_at' => now()
            ]);

            return $invoice->fresh();
        });
    }

    private function canConvert(Quote $quote): bool
    {
        // Un devis peut être converti s'il est accepté et pas encore converti
        return $quote->status === QuoteStatus::ACCEPTED->value;
    }

    private function generateInvoiceNumber(): string
    {
        $company = Auth::user()->company;
        $prefix = $company->invoice_prefix ?? 'FACT';
        $year = now()->format('Y');
        
        // Trouver le dernier numéro de facture pour cette entreprise cette année
        $lastInvoice = Invoice::where('company_id', $company->id)
            ->whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastInvoice && preg_match('/(\d+)$/', $lastInvoice->invoice_number, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $year, $nextNumber);
    }
}
