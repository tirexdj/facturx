<?php

namespace App\Domain\Quote\Listeners;

use App\Domain\Quote\Events\QuoteStatusChanged;
use App\Domain\Shared\Services\Pdf\PdfGeneratorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class HandleQuoteStatusChange implements ShouldQueue
{
    public function __construct(
        protected PdfGeneratorService $pdfGenerator
    ) {}

    public function handle(QuoteStatusChanged $event): void
    {
        $quote = $event->quote;
        $oldStatus = $event->oldStatus;
        $newStatus = $event->newStatus;

        Log::info('Quote status changed', [
            'quote_id' => $quote->id,
            'quote_number' => $quote->quote_number,
            'old_status' => $oldStatus->value,
            'new_status' => $newStatus->value,
            'reason' => $event->reason,
            'user_id' => $event->userId,
        ]);

        // Régénérer le PDF si nécessaire
        if ($this->shouldRegeneratePdf($oldStatus, $newStatus)) {
            try {
                $this->pdfGenerator->generateQuotePdf($quote);
                Log::info('PDF regenerated for quote status change', [
                    'quote_id' => $quote->id,
                    'new_status' => $newStatus->value,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to regenerate PDF for quote', [
                    'quote_id' => $quote->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Autres actions selon le nouveau statut
        match($newStatus) {
            \App\Domain\Shared\Enums\QuoteStatus::SENT => $this->handleQuoteSent($quote),
            \App\Domain\Shared\Enums\QuoteStatus::ACCEPTED => $this->handleQuoteAccepted($quote),
            \App\Domain\Shared\Enums\QuoteStatus::REJECTED => $this->handleQuoteRejected($quote),
            \App\Domain\Shared\Enums\QuoteStatus::EXPIRED => $this->handleQuoteExpired($quote),
            default => null,
        };
    }

    protected function shouldRegeneratePdf($oldStatus, $newStatus): bool
    {
        // Régénérer le PDF lors de certaines transitions
        return in_array($newStatus, [
            \App\Domain\Shared\Enums\QuoteStatus::SENT,
            \App\Domain\Shared\Enums\QuoteStatus::ACCEPTED,
        ]);
    }

    protected function handleQuoteSent($quote): void
    {
        // Programmer des relances si nécessaire
        // Notifier l'équipe commerciale
        Log::info('Quote sent - additional processing', ['quote_id' => $quote->id]);
    }

    protected function handleQuoteAccepted($quote): void
    {
        // Programmer la création automatique de facture si configuré
        // Notifier l'équipe
        Log::info('Quote accepted - additional processing', ['quote_id' => $quote->id]);
    }

    protected function handleQuoteRejected($quote): void
    {
        // Notifier l'équipe commerciale
        // Programmer des actions de suivi
        Log::info('Quote rejected - additional processing', ['quote_id' => $quote->id]);
    }

    protected function handleQuoteExpired($quote): void
    {
        // Notifier l'équipe commerciale
        // Proposer un renouvellement
        Log::info('Quote expired - additional processing', ['quote_id' => $quote->id]);
    }
}
