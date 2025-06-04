<?php

namespace App\Services;

use App\Models\Quote;
use App\Models\Invoice;
use App\Mail\QuoteMail;
use App\Mail\InvoiceMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class EmailService
{
    /**
     * Envoyer un devis par email
     */
    public function sendQuote(Quote $quote, string $email, string $subject, string $message, string $pdfPath): bool
    {
        try {
            Mail::to($email)->send(new QuoteMail(
                $quote,
                $subject,
                $message,
                $pdfPath
            ));

            Log::info('Quote email sent successfully', [
                'quote_id' => $quote->id,
                'quote_number' => $quote->quote_number,
                'recipient' => $email
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send quote email', [
                'quote_id' => $quote->id,
                'quote_number' => $quote->quote_number,
                'recipient' => $email,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Envoyer une facture par email
     */
    public function sendInvoice(Invoice $invoice, string $email, string $subject, string $message, string $pdfPath): bool
    {
        try {
            Mail::to($email)->send(new InvoiceMail(
                $invoice,
                $subject,
                $message,
                $pdfPath
            ));

            Log::info('Invoice email sent successfully', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'recipient' => $email
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send invoice email', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'recipient' => $email,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Envoyer un email de relance pour facture impayée
     */
    public function sendPaymentReminder(Invoice $invoice, string $email, int $reminderLevel = 1): bool
    {
        try {
            $subject = $this->getPaymentReminderSubject($invoice, $reminderLevel);
            $message = $this->getPaymentReminderMessage($invoice, $reminderLevel);

            // Générer le PDF de la facture
            $pdfService = app(PdfGeneratorService::class);
            $pdfContent = $pdfService->generateInvoicePdf($invoice);
            $pdfPath = "temp/invoices/facture-{$invoice->invoice_number}.pdf";
            Storage::put($pdfPath, $pdfContent);

            try {
                $this->sendInvoice($invoice, $email, $subject, $message, $pdfPath);
                return true;
            } finally {
                Storage::delete($pdfPath);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send payment reminder', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'recipient' => $email,
                'reminder_level' => $reminderLevel,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Obtenir l'objet de l'email de relance
     */
    private function getPaymentReminderSubject(Invoice $invoice, int $level): string
    {
        return match($level) {
            1 => "Rappel - Facture {$invoice->invoice_number} en attente de paiement",
            2 => "Relance - Facture {$invoice->invoice_number} échue",
            3 => "Dernière relance - Facture {$invoice->invoice_number} en retard",
            default => "Facture {$invoice->invoice_number} - Relance de paiement"
        };
    }

    /**
     * Obtenir le message de l'email de relance
     */
    private function getPaymentReminderMessage(Invoice $invoice, int $level): string
    {
        $daysOverdue = now()->diffInDays($invoice->due_date);
        $company = $invoice->company;

        return match($level) {
            1 => "Bonjour,

Nous vous informons que la facture {$invoice->invoice_number} d'un montant de " . number_format($invoice->total, 2, ',', ' ') . " € émise le " . $invoice->invoice_date->format('d/m/Y') . " arrive à échéance le " . $invoice->due_date->format('d/m/Y') . ".

Merci de procéder au règlement dans les meilleurs délais.

Cordialement,
{$company->name}",

            2 => "Bonjour,

Nous constatons que la facture {$invoice->invoice_number} d'un montant de " . number_format($invoice->total, 2, ',', ' ') . " € est échue depuis {$daysOverdue} jour(s).

Merci de régulariser cette situation rapidement.

En cas de litige ou de problème, n'hésitez pas à nous contacter.

Cordialement,
{$company->name}",

            3 => "Bonjour,

Malgré nos précédents rappels, la facture {$invoice->invoice_number} d'un montant de " . number_format($invoice->total, 2, ',', ' ') . " € demeure impayée depuis {$daysOverdue} jour(s).

Nous vous demandons de procéder au règlement immédiat de cette facture.

À défaut de règlement sous 8 jours, nous nous verrons contraints d'engager une procédure de recouvrement.

Cordialement,
{$company->name}",

            default => "Bonjour,

Nous vous rappelons que la facture {$invoice->invoice_number} est en attente de paiement.

Merci de procéder au règlement.

Cordialement,
{$company->name}"
        };
    }

    /**
     * Envoyer un email de notification de paiement reçu
     */
    public function sendPaymentConfirmation(Invoice $invoice, string $email): bool
    {
        try {
            $subject = "Confirmation de paiement - Facture {$invoice->invoice_number}";
            $message = "Bonjour,

Nous vous confirmons avoir bien reçu votre paiement de " . number_format($invoice->total, 2, ',', ' ') . " € pour la facture {$invoice->invoice_number}.

Nous vous remercions pour votre règlement.

Cordialement,
{$invoice->company->name}";

            Mail::raw($message, function ($mail) use ($email, $subject) {
                $mail->to($email)->subject($subject);
            });

            Log::info('Payment confirmation email sent', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'recipient' => $email
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send payment confirmation', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'recipient' => $email,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Valider une adresse email
     */
    public function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Obtenir la liste des templates d'email disponibles
     */
    public function getEmailTemplates(): array
    {
        return [
            'quote' => [
                'name' => 'Envoi de devis',
                'description' => 'Template pour l\'envoi de devis aux clients',
                'subject' => 'Devis {quote_number}',
                'template' => 'emails.quote'
            ],
            'invoice' => [
                'name' => 'Envoi de facture',
                'description' => 'Template pour l\'envoi de factures aux clients',
                'subject' => 'Facture {invoice_number}',
                'template' => 'emails.invoice'
            ],
            'reminder_1' => [
                'name' => 'Première relance',
                'description' => 'Premier rappel de paiement',
                'subject' => 'Rappel - Facture {invoice_number}',
                'template' => 'emails.reminder'
            ],
            'reminder_2' => [
                'name' => 'Deuxième relance',
                'description' => 'Deuxième rappel de paiement',
                'subject' => 'Relance - Facture {invoice_number}',
                'template' => 'emails.reminder'
            ],
            'reminder_3' => [
                'name' => 'Dernière relance',
                'description' => 'Dernier rappel avant procédure',
                'subject' => 'Dernière relance - Facture {invoice_number}',
                'template' => 'emails.reminder'
            ]
        ];
    }
}
