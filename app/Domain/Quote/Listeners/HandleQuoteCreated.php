<?php

namespace App\Domain\Quote\Listeners;

use App\Domain\Quote\Events\QuoteCreated;
use App\Domain\Shared\Services\Pdf\PdfGeneratorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class HandleQuoteCreated implements ShouldQueue
{
    public function __construct(
        protected PdfGeneratorService $pdfGenerator
    ) {}

    public function handle(QuoteCreated $event): void
    {
        $quote = $event->quote;

        Log::info('Quote created', [
            'quote_id' => $quote->id,
            'quote_number' => $quote->quote_number,
            'company_id' => $quote->company_id,
            'client_id' => $quote->client_id,
        ]);

        // Calculer les totaux si pas encore fait
        if ($quote->total_gross == 0 && $quote->lines()->count() > 0) {
            $quote->calculateTotals();
            $quote->save();
        }

        // Générer le PDF initial en arrière-plan
        try {
            $this->pdfGenerator->generateQuotePdf($quote);
            Log::info('Initial PDF generated for new quote', ['quote_id' => $quote->id]);
        } catch (\Exception $e) {
            Log::error('Failed to generate initial PDF for quote', [
                'quote_id' => $quote->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Autres actions initiales
        $this->initializeQuoteSettings($quote);
    }

    protected function initializeQuoteSettings($quote): void
    {
        // Appliquer les paramètres par défaut de l'entreprise
        $company = $quote->company;

        if (!$quote->payment_terms && $company->default_quote_payment_terms) {
            $quote->payment_terms = $company->default_quote_payment_terms;
        }

        if (!$quote->terms && $company->default_quote_terms) {
            $quote->terms = $company->default_quote_terms;
        }

        if (!$quote->footer && $company->default_quote_footer) {
            $quote->footer = $company->default_quote_footer;
        }

        // Définir si c'est un devis payant selon les paramètres de l'entreprise
        if ($company->quotes_are_billable_by_default) {
            $quote->is_billable = true;
        }

        // Appliquer le pourcentage d'acompte par défaut
        if (!$quote->deposit_percentage && $company->default_deposit_percentage) {
            $quote->deposit_percentage = $company->default_deposit_percentage;
        }

        $quote->save();
    }
}
