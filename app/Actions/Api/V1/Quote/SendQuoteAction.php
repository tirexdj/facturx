<?php

namespace App\Actions\Api\V1\Quote;

use App\Models\Quote;
use App\Enums\QuoteStatus;
use App\Services\PdfGeneratorService;
use App\Services\EmailService;
use App\Mail\QuoteMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SendQuoteAction
{
    public function __construct(
        protected PdfGeneratorService $pdfService,
        protected EmailService $emailService
    ) {}

    public function execute(Quote $quote, array $data): array
    {
        // Vérifier que le devis peut être envoyé
        if (!$this->canSend($quote)) {
            throw new \Exception('Ce devis ne peut pas être envoyé.');
        }

        // Générer le PDF
        $pdfContent = $this->pdfService->generateQuotePdf($quote);
        $pdfFileName = "devis-{$quote->quote_number}.pdf";
        
        // Stocker temporairement le PDF
        $pdfPath = "temp/quotes/{$pdfFileName}";
        Storage::put($pdfPath, $pdfContent);

        try {
            // Envoyer l'email
            $recipient = $data['email'] ?? $quote->customer->email;
            $subject = $data['subject'] ?? "Devis {$quote->quote_number}";
            $message = $data['message'] ?? $this->getDefaultMessage($quote);

            Mail::to($recipient)->send(new QuoteMail(
                $quote,
                $subject,
                $message,
                $pdfPath
            ));

            // Mettre à jour le statut du devis
            $quote->update([
                'status' => QuoteStatus::SENT->value,
                'sent_at' => now()
            ]);

            // Ajouter un historique de statut
            $quote->statusHistories()->create([
                'status' => QuoteStatus::SENT->value,
                'comment' => "Devis envoyé à {$recipient}",
                'user_id' => auth()->id(),
                'created_at' => now()
            ]);

            return [
                'sent_at' => now(),
                'recipient' => $recipient,
                'subject' => $subject
            ];

        } finally {
            // Nettoyer le fichier temporaire
            Storage::delete($pdfPath);
        }
    }

    private function canSend(Quote $quote): bool
    {
        // Un devis peut être envoyé s'il n'est pas expiré, refusé ou converti
        return !in_array($quote->status, [
            QuoteStatus::EXPIRED->value,
            QuoteStatus::DECLINED->value,
            QuoteStatus::CONVERTED->value
        ]);
    }

    private function getDefaultMessage(Quote $quote): string
    {
        return "Bonjour,

Veuillez trouver ci-joint le devis {$quote->quote_number} en date du " . $quote->quote_date->format('d/m/Y') . ".

Ce devis est valable jusqu'au " . $quote->valid_until->format('d/m/Y') . ".

N'hésitez pas à nous contacter pour toute question.

Cordialement,
{$quote->company->name}";
    }
}
