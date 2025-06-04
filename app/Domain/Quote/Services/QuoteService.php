<?php

namespace App\Domain\Quote\Services;

use App\Domain\Quote\Models\Quote;
use App\Domain\Shared\Enums\QuoteStatus;
use App\Domain\Shared\Services\Pdf\PdfGeneratorService;
use App\Domain\Quote\Events\QuoteSent;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class QuoteService
{
    public function __construct(
        protected PdfGeneratorService $pdfGenerator
    ) {}

    /**
     * Crée un nouveau devis
     */
    public function createQuote(array $data): Quote
    {
        // Déterminer automatiquement le type de document selon le secteur
        if (!isset($data['is_purchase_order'])) {
            $data['is_purchase_order'] = $this->shouldBePurchaseOrder($data);
        }

        $quote = Quote::create($data);

        // Créer les lignes si fournies
        if (!empty($data['lines'])) {
            foreach ($data['lines'] as $lineData) {
                $quote->lines()->create($lineData);
            }
            
            // Recalculer les totaux
            $quote->calculateTotals();
            $quote->save();
        }

        return $quote->fresh(['lines', 'client', 'company']);
    }

    /**
     * Met à jour un devis
     */
    public function updateQuote(Quote $quote, array $data): Quote
    {
        if (!$quote->canBeEdited()) {
            throw new \InvalidArgumentException('Ce devis ne peut plus être modifié');
        }

        $quote->update($data);

        // Mettre à jour les lignes si fournies
        if (isset($data['lines'])) {
            // Supprimer les lignes existantes
            $quote->lines()->delete();
            
            // Créer les nouvelles lignes
            foreach ($data['lines'] as $lineData) {
                $quote->lines()->create($lineData);
            }
            
            // Recalculer les totaux
            $quote->calculateTotaux();
            $quote->save();
        }

        return $quote->fresh(['lines', 'client', 'company']);
    }

    /**
     * Envoie un devis par email
     */
    public function sendQuote(Quote $quote, array $emailData): bool
    {
        if (!$quote->canBeSent()) {
            throw new \InvalidArgumentException('Ce devis ne peut pas être envoyé');
        }

        // Générer ou régénérer le PDF
        $pdfPath = $this->pdfGenerator->generateQuotePdf($quote);
        
        // Mettre à jour le statut
        $quote->updateStatus(QuoteStatus::SENT);

        // Déclencher l'événement d'envoi
        event(new QuoteSent(
            $quote,
            $emailData['recipient'],
            $emailData['message'] ?? null
        ));

        return true;
    }

    /**
     * Accepte un devis (côté client)
     */
    public function acceptQuote(Quote $quote, array $acceptanceData = []): Quote
    {
        if (!in_array($quote->status, [QuoteStatus::SENT, QuoteStatus::PENDING])) {
            throw new \InvalidArgumentException('Ce devis ne peut pas être accepté');
        }

        // Sauvegarder les données de signature si fournies
        if (!empty($acceptanceData['signature_data'])) {
            $quote->signature_data = $acceptanceData['signature_data'];
        }

        $quote->updateStatus(
            QuoteStatus::ACCEPTED,
            $acceptanceData['message'] ?? 'Accepté par le client'
        );

        return $quote;
    }

    /**
     * Rejette un devis (côté client)
     */
    public function rejectQuote(Quote $quote, string $reason): Quote
    {
        if (!in_array($quote->status, [QuoteStatus::SENT, QuoteStatus::PENDING])) {
            throw new \InvalidArgumentException('Ce devis ne peut pas être rejeté');
        }

        $quote->updateStatus(QuoteStatus::REJECTED, $reason);

        return $quote;
    }

    /**
     * Convertit un devis en facture
     */
    public function convertToInvoice(Quote $quote): \App\Domain\Invoice\Models\Invoice
    {
        if (!$quote->canBeConverted()) {
            throw new \InvalidArgumentException('Ce devis ne peut pas être converti en facture');
        }

        // Données pour la nouvelle facture
        $invoiceData = [
            'company_id' => $quote->company_id,
            'client_id' => $quote->client_id,
            'quote_id' => $quote->id,
            'reference' => $quote->reference,
            'currency_code' => $quote->currency_code,
            'exchange_rate' => $quote->exchange_rate,
            'discount_type' => $quote->discount_type,
            'discount_value' => $quote->discount_value,
            'discount_amount' => $quote->discount_amount,
            'notes' => $quote->notes,
            'terms' => $quote->terms,
            'footer' => $quote->footer,
            'created_by' => auth()->id(),
        ];

        $invoice = \App\Domain\Invoice\Models\Invoice::create($invoiceData);

        // Copier les lignes
        foreach ($quote->lines as $quoteLine) {
            $invoice->lines()->create([
                'line_type' => $quoteLine->line_type,
                'product_id' => $quoteLine->product_id,
                'service_id' => $quoteLine->service_id,
                'product_variant_id' => $quoteLine->product_variant_id,
                'title' => $quoteLine->title,
                'description' => $quoteLine->description,
                'quantity' => $quoteLine->quantity,
                'unit_id' => $quoteLine->unit_id,
                'unit_price_net' => $quoteLine->unit_price_net,
                'vat_rate_id' => $quoteLine->vat_rate_id,
                'discount_type' => $quoteLine->discount_type,
                'discount_value' => $quoteLine->discount_value,
                'discount_amount' => $quoteLine->discount_amount,
                'subtotal_net' => $quoteLine->subtotal_net,
                'tax_amount' => $quoteLine->tax_amount,
                'total_net' => $quoteLine->total_net,
                'position' => $quoteLine->position,
                'created_by' => auth()->id(),
            ]);
        }

        // Recalculer les totaux de la facture
        $invoice->calculateTotals();
        $invoice->save();

        return $invoice;
    }

    /**
     * Duplique un devis
     */
    public function duplicateQuote(Quote $originalQuote, array $overrides = []): Quote
    {
        $data = array_merge([
            'company_id' => $originalQuote->company_id,
            'client_id' => $originalQuote->client_id,
            'reference' => $originalQuote->reference,
            'title' => $originalQuote->title . ' (Copie)',
            'introduction' => $originalQuote->introduction,
            'currency_code' => $originalQuote->currency_code,
            'exchange_rate' => $originalQuote->exchange_rate,
            'discount_type' => $originalQuote->discount_type,
            'discount_value' => $originalQuote->discount_value,
            'notes' => $originalQuote->notes,
            'terms' => $originalQuote->terms,
            'footer' => $originalQuote->footer,
            'is_purchase_order' => $originalQuote->is_purchase_order,
            'is_billable' => $originalQuote->is_billable,
            'deposit_percentage' => $originalQuote->deposit_percentage,
            'payment_terms' => $originalQuote->payment_terms,
            'template_name' => $originalQuote->template_name,
            'template_config' => $originalQuote->template_config,
            'legal_mentions' => $originalQuote->legal_mentions,
            'created_by' => auth()->id(),
        ], $overrides);

        $newQuote = Quote::create($data);

        // Dupliquer les lignes
        foreach ($originalQuote->lines as $line) {
            $newQuote->lines()->create([
                'line_type' => $line->line_type,
                'product_id' => $line->product_id,
                'service_id' => $line->service_id,
                'product_variant_id' => $line->product_variant_id,
                'title' => $line->title,
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unit_id' => $line->unit_id,
                'unit_price_net' => $line->unit_price_net,
                'vat_rate_id' => $line->vat_rate_id,
                'discount_type' => $line->discount_type,
                'discount_value' => $line->discount_value,
                'discount_amount' => $line->discount_amount,
                'subtotal_net' => $line->subtotal_net,
                'tax_amount' => $line->tax_amount,
                'total_net' => $line->total_net,
                'position' => $line->position,
                'is_optional' => $line->is_optional,
                'created_by' => auth()->id(),
            ]);
        }

        // Recalculer les totaux
        $newQuote->calculateTotals();
        $newQuote->save();

        return $newQuote->fresh(['lines', 'client', 'company']);
    }

    /**
     * Génère le PDF d'un devis
     */
    public function generatePdf(Quote $quote, array $options = []): string
    {
        return $this->pdfGenerator->generateQuotePdf($quote, $options);
    }

    /**
     * Génère un aperçu PDF sans sauvegarder
     */
    public function generatePreview(Quote $quote, array $options = []): string
    {
        return $this->pdfGenerator->generatePreview($quote, $options);
    }

    /**
     * Expire automatiquement les devis
     */
    public function expireQuotes(): int
    {
        $expiredCount = 0;
        
        $expiredQuotes = Quote::expired()
            ->whereNotIn('status', [
                QuoteStatus::ACCEPTED,
                QuoteStatus::REJECTED,
                QuoteStatus::CANCELLED,
                QuoteStatus::EXPIRED
            ])
            ->get();

        foreach ($expiredQuotes as $quote) {
            $quote->updateStatus(QuoteStatus::EXPIRED, 'Expiré automatiquement');
            $expiredCount++;
        }

        return $expiredCount;
    }

    /**
     * Calcule les statistiques des devis
     */
    public function getQuoteStatistics($companyId, $period = null): array
    {
        $query = Quote::where('company_id', $companyId);
        
        if ($period) {
            $query->whereBetween('date', [
                $period['start'],
                $period['end']
            ]);
        }

        $quotes = $query->get();

        return [
            'total_quotes' => $quotes->count(),
            'total_amount' => $quotes->sum('total_gross'),
            'average_amount' => $quotes->avg('total_gross'),
            'by_status' => [
                'draft' => $quotes->where('status', QuoteStatus::DRAFT)->count(),
                'sent' => $quotes->where('status', QuoteStatus::SENT)->count(),
                'pending' => $quotes->where('status', QuoteStatus::PENDING)->count(),
                'accepted' => $quotes->where('status', QuoteStatus::ACCEPTED)->count(),
                'rejected' => $quotes->where('status', QuoteStatus::REJECTED)->count(),
                'expired' => $quotes->where('status', QuoteStatus::EXPIRED)->count(),
                'cancelled' => $quotes->where('status', QuoteStatus::CANCELLED)->count(),
            ],
            'conversion_rate' => $this->calculateConversionRate($quotes),
            'average_processing_time' => $this->calculateAverageProcessingTime($quotes),
        ];
    }

    /**
     * Détermine si le document doit être un bon de commande
     */
    protected function shouldBePurchaseOrder(array $data): bool
    {
        // Logique pour déterminer automatiquement selon le secteur d'activité
        $purchaseOrderSectors = [
            'construction',
            'manufacturing',
            'retail',
            'wholesale',
            'automotive',
        ];

        if (isset($data['company_id'])) {
            $company = \App\Domain\Company\Models\Company::find($data['company_id']);
            if ($company && in_array($company->business_sector, $purchaseOrderSectors)) {
                return true;
            }
        }

        // Vérifier dans le titre ou la description
        $title = strtolower($data['title'] ?? '');
        return str_contains($title, 'commande') || 
               str_contains($title, 'order') ||
               str_contains($title, 'fourniture');
    }

    /**
     * Calcule le taux de conversion
     */
    protected function calculateConversionRate($quotes): float
    {
        $totalQuotes = $quotes->whereNotIn('status', [QuoteStatus::DRAFT])->count();
        $acceptedQuotes = $quotes->where('status', QuoteStatus::ACCEPTED)->count();

        return $totalQuotes > 0 ? ($acceptedQuotes / $totalQuotes) * 100 : 0;
    }

    /**
     * Calcule le temps moyen de traitement
     */
    protected function calculateAverageProcessingTime($quotes): ?float
    {
        $processedQuotes = $quotes->whereNotNull('sent_at')
            ->filter(function ($quote) {
                return in_array($quote->status, [
                    QuoteStatus::ACCEPTED,
                    QuoteStatus::REJECTED
                ]);
            });

        if ($processedQuotes->isEmpty()) {
            return null;
        }

        $totalDays = $processedQuotes->sum(function ($quote) {
            $endDate = $quote->accepted_at ?? $quote->rejected_at;
            return $quote->sent_at->diffInDays($endDate);
        });

        return $totalDays / $processedQuotes->count();
    }

    /**
     * Valide les données d'un devis
     */
    public function validateQuoteData(array $data): array
    {
        $errors = [];

        // Validation des champs obligatoires
        if (empty($data['company_id'])) {
            $errors['company_id'] = 'L\'entreprise est obligatoire';
        }

        if (empty($data['client_id'])) {
            $errors['client_id'] = 'Le client est obligatoire';
        }

        if (empty($data['title'])) {
            $errors['title'] = 'Le titre est obligatoire';
        }

        // Validation des lignes
        if (!empty($data['lines'])) {
            foreach ($data['lines'] as $index => $line) {
                if (empty($line['title'])) {
                    $errors["lines.{$index}.title"] = 'Le titre de la ligne est obligatoire';
                }

                if (!isset($line['quantity']) || $line['quantity'] <= 0) {
                    $errors["lines.{$index}.quantity"] = 'La quantité doit être supérieure à 0';
                }

                if (!isset($line['unit_price_net']) || $line['unit_price_net'] < 0) {
                    $errors["lines.{$index}.unit_price_net"] = 'Le prix unitaire ne peut pas être négatif';
                }
            }
        }

        // Validation des dates
        if (!empty($data['validity_date'])) {
            $validityDate = \Carbon\Carbon::parse($data['validity_date']);
            if ($validityDate->isPast()) {
                $errors['validity_date'] = 'La date de validité ne peut pas être dans le passé';
            }
        }

        // Validation des pourcentages
        if (isset($data['deposit_percentage']) && ($data['deposit_percentage'] < 0 || $data['deposit_percentage'] > 100)) {
            $errors['deposit_percentage'] = 'Le pourcentage d\'acompte doit être entre 0 et 100';
        }

        return $errors;
    }
}
