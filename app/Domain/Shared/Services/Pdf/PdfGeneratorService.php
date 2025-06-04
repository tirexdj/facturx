<?php

namespace App\Domain\Shared\Services\Pdf;

use App\Domain\Company\Models\Company;
use App\Domain\Quote\Models\Quote;
use App\Domain\Invoice\Models\Invoice;
use App\Domain\Shared\Enums\DocumentType;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class PdfGeneratorService
{
    protected array $config;
    protected ?Company $company = null;

    public function __construct()
    {
        $this->config = config('pdf.default', [
            'paper' => 'A4',
            'orientation' => 'portrait',
            'margins' => [
                'top' => 20,
                'right' => 15,
                'bottom' => 20,
                'left' => 15,
            ]
        ]);
    }

    /**
     * Configure le service pour une entreprise spécifique
     */
    public function forCompany(Company $company): self
    {
        $this->company = $company;
        return $this;
    }

    /**
     * Génère un PDF pour un devis
     */
    public function generateQuotePdf(Quote $quote, array $options = []): string
    {
        $this->company = $quote->company;
        
        $data = [
            'quote' => $quote,
            'company' => $this->company,
            'config' => $this->getPdfConfig(),
            'mentions_legales' => $this->getLegalMentions(DocumentType::QUOTE),
            'is_purchase_order' => $this->isPurchaseOrder($quote),
        ];

        $template = $options['template'] ?? $this->getTemplate(DocumentType::QUOTE);
        $view = "pdf.documents.{$template}";

        if (!View::exists($view)) {
            $view = 'pdf.documents.quote';
        }

        $pdf = Pdf::loadView($view, $data);
        
        // Configuration du PDF
        $pdf->setPaper($this->config['paper'], $this->config['orientation']);
        
        // Options personnalisées
        if (isset($options['margins'])) {
            $pdf->setOptions([
                'margin_top' => $options['margins']['top'] ?? $this->config['margins']['top'],
                'margin_right' => $options['margins']['right'] ?? $this->config['margins']['right'],
                'margin_bottom' => $options['margins']['bottom'] ?? $this->config['margins']['bottom'],
                'margin_left' => $options['margins']['left'] ?? $this->config['margins']['left'],
            ]);
        }

        $filename = $this->generateFilename($quote->quote_number, DocumentType::QUOTE);
        $pdfContent = $pdf->output();

        // Sauvegarde du fichier
        $path = "documents/quotes/{$this->company->id}/{$filename}";
        Storage::disk('local')->put($path, $pdfContent);

        // Mettre à jour le chemin du PDF dans le modèle
        $quote->update(['pdf_path' => $path]);

        return $path;
    }

    /**
     * Génère un PDF pour une facture
     */
    public function generateInvoicePdf(Invoice $invoice, array $options = []): string
    {
        $this->company = $invoice->company;
        
        $data = [
            'invoice' => $invoice,
            'company' => $this->company,
            'config' => $this->getPdfConfig(),
            'mentions_legales' => $this->getLegalMentions(DocumentType::INVOICE),
        ];

        $template = $options['template'] ?? $this->getTemplate(DocumentType::INVOICE);
        $view = "pdf.documents.{$template}";

        if (!View::exists($view)) {
            $view = 'pdf.documents.invoice';
        }

        $pdf = Pdf::loadView($view, $data);
        $pdf->setPaper($this->config['paper'], $this->config['orientation']);

        $filename = $this->generateFilename($invoice->invoice_number, DocumentType::INVOICE);
        $pdfContent = $pdf->output();

        $path = "documents/invoices/{$this->company->id}/{$filename}";
        Storage::disk('local')->put($path, $pdfContent);

        return $path;
    }

    /**
     * Génère le nom de fichier
     */
    protected function generateFilename(string $numero, DocumentType $type): string
    {
        $prefix = $type->prefix();
        $date = now()->format('Y-m-d');
        $numero = Str::slug($numero);
        return "{$prefix}_{$numero}_{$date}.pdf";
    }

    /**
     * Récupère la configuration PDF pour l'entreprise
     */
    protected function getPdfConfig(): array
    {
        if (!$this->company) {
            return $this->config;
        }

        return array_merge($this->config, [
            'logo' => $this->company->getFirstMediaUrl('logo'),
            'colors' => [
                'primary' => $this->company->brand_color_primary ?? '#3B82F6',
                'secondary' => $this->company->brand_color_secondary ?? '#6B7280',
                'accent' => $this->company->brand_color_accent ?? '#10B981',
                'text' => $this->company->brand_color_text ?? '#1F2937',
            ],
            'fonts' => [
                'primary' => $this->company->pdf_font_primary ?? 'DejaVu Sans',
                'secondary' => $this->company->pdf_font_secondary ?? 'DejaVu Sans',
            ],
            'header' => [
                'show' => $this->company->pdf_show_header ?? true,
                'height' => $this->company->pdf_header_height ?? 80,
                'content' => $this->company->pdf_header_content,
            ],
            'footer' => [
                'show' => $this->company->pdf_show_footer ?? true,
                'height' => $this->company->pdf_footer_height ?? 50,
                'content' => $this->company->pdf_footer_content,
            ],
            'watermark' => [
                'show' => $this->company->pdf_show_watermark ?? false,
                'text' => $this->company->pdf_watermark_text,
                'opacity' => $this->company->pdf_watermark_opacity ?? 0.1,
            ],
        ]);
    }

    /**
     * Récupère les mentions légales selon le type de document
     */
    protected function getLegalMentions(DocumentType $type): array
    {
        $mentions = [];

        if (!$this->company) {
            return $mentions;
        }

        // Mentions communes obligatoires
        $mentions['company_info'] = [
            'name' => $this->company->name,
            'legal_form' => $this->company->legal_form,
            'capital' => $this->company->capital,
            'siret' => $this->company->siret,
            'rcs' => $this->company->rcs,
            'ape_code' => $this->company->ape_code,
            'vat_number' => $this->company->vat_number,
            'address' => $this->company->getFullAddress(),
        ];

        // Mentions spécifiques selon le type de document
        switch ($type) {
            case DocumentType::QUOTE:
                $validityDays = $this->company->quote_validity_days ?? 30;
                $mentions['validity'] = "Ce devis est valable {$validityDays} jours à compter de sa date d'émission.";
                $mentions['payment'] = $this->company->quote_payment_terms ?? 'Conditions de paiement selon les conditions générales de vente.';
                
                if ($this->company->quote_require_deposit) {
                    $depositPercent = $this->company->quote_deposit_percentage ?? 30;
                    $mentions['deposit'] = "Un acompte de {$depositPercent}% sera demandé à la commande.";
                }

                $mentions['acceptance'] = 'Pour accepter ce devis, veuillez le signer et le retourner avec la mention "Bon pour accord".';
                break;

            case DocumentType::PURCHASE_ORDER:
                $mentions['order'] = 'Ce bon de commande fait foi de votre engagement d\'achat.';
                $mentions['delivery'] = $this->company->default_delivery_terms ?? 'Délai de livraison selon les conditions générales de vente.';
                break;

            case DocumentType::INVOICE:
                $mentions['payment_terms'] = $this->company->invoice_payment_terms ?? 'Paiement à 30 jours fin de mois.';
                
                // Pénalités de retard obligatoires
                $lateRate = $this->company->late_penalty_rate ?? 3;
                $mentions['late_penalties'] = "En cas de retard de paiement, des pénalités de {$lateRate} fois le taux d'intérêt légal seront appliquées.";
                
                // Indemnité forfaitaire de recouvrement obligatoire
                $mentions['recovery_fee'] = 'Indemnité forfaitaire de recouvrement : 40€ (art. L441-10 du Code de commerce).';
                
                // LRAR obligatoire si dépassement
                $mentions['lrar_requirement'] = 'Les frais de recouvrement sont à la charge du débiteur.';
                break;
        }

        // Mentions TVA selon le régime
        if ($this->company->vat_regime === 'micro') {
            $mentions['vat'] = 'TVA non applicable, art. 293 B du CGI - Régime micro-entreprise.';
        } elseif ($this->company->vat_regime === 'franchise') {
            $mentions['vat'] = 'TVA non applicable, art. 293 B du CGI - Franchise en base de TVA.';
        }

        // Mention profession réglementée si applicable
        if ($this->company->is_regulated_profession) {
            $mentions['regulation'] = $this->company->regulation_text;
        }

        // Mention assurance professionnelle si applicable
        if ($this->company->professional_insurance) {
            $mentions['insurance'] = $this->company->insurance_details;
        }

        return $mentions;
    }

    /**
     * Détermine si c'est un bon de commande
     */
    protected function isPurchaseOrder(Quote $quote): bool
    {
        // Logique pour déterminer si c'est un bon de commande selon le secteur d'activité
        $purchaseOrderSectors = [
            'construction',
            'manufacturing',
            'retail',
            'wholesale',
        ];

        return in_array($this->company->business_sector, $purchaseOrderSectors) || 
               str_contains(strtolower($quote->title ?? ''), 'commande');
    }

    /**
     * Récupère le template à utiliser
     */
    protected function getTemplate(DocumentType $type): string
    {
        $templateField = match($type) {
            DocumentType::QUOTE => 'pdf_template_quote',
            DocumentType::PURCHASE_ORDER => 'pdf_template_purchase_order',
            DocumentType::INVOICE => 'pdf_template_invoice',
            DocumentType::CREDIT_NOTE => 'pdf_template_credit_note',
            default => 'pdf_template_default',
        };

        return $this->company->{$templateField} ?? $type->template();
    }

    /**
     * Récupère un PDF existant
     */
    public function getPdf(string $path): ?string
    {
        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->get($path);
        }

        return null;
    }

    /**
     * Supprime un PDF
     */
    public function deletePdf(string $path): bool
    {
        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->delete($path);
        }

        return false;
    }

    /**
     * Génère une preview du PDF sans sauvegarder
     */
    public function generatePreview(Quote|Invoice $document, array $options = []): string
    {
        if ($document instanceof Quote) {
            return $this->generateQuotePreview($document, $options);
        }

        return $this->generateInvoicePreview($document, $options);
    }

    protected function generateQuotePreview(Quote $quote, array $options = []): string
    {
        $this->company = $quote->company;
        
        $data = [
            'quote' => $quote,
            'company' => $this->company,
            'config' => $this->getPdfConfig(),
            'mentions_legales' => $this->getLegalMentions(DocumentType::QUOTE),
            'is_purchase_order' => $this->isPurchaseOrder($quote),
            'is_preview' => true,
        ];

        $template = $options['template'] ?? $this->getTemplate(DocumentType::QUOTE);
        $view = "pdf.documents.{$template}";

        if (!View::exists($view)) {
            $view = 'pdf.documents.quote';
        }

        $pdf = Pdf::loadView($view, $data);
        $pdf->setPaper($this->config['paper'], $this->config['orientation']);

        return $pdf->output();
    }

    protected function generateInvoicePreview(Invoice $invoice, array $options = []): string
    {
        $this->company = $invoice->company;
        
        $data = [
            'invoice' => $invoice,
            'company' => $this->company,
            'config' => $this->getPdfConfig(),
            'mentions_legales' => $this->getLegalMentions(DocumentType::INVOICE),
            'is_preview' => true,
        ];

        $template = $options['template'] ?? $this->getTemplate(DocumentType::INVOICE);
        $view = "pdf.documents.{$template}";

        if (!View::exists($view)) {
            $view = 'pdf.documents.invoice';
        }

        $pdf = Pdf::loadView($view, $data);
        $pdf->setPaper($this->config['paper'], $this->config['orientation']);

        return $pdf->output();
    }
}
