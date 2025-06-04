<?php

namespace App\Services\Pdf;

use App\Models\Company;
use App\Models\Devis;
use App\Models\Facture;
use App\Enums\DocumentType;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class PdfGeneratorService
{
    protected array $config;
    protected ?Company $company = null;

    public function __construct()
    {
        $this->config = config('pdf.default');
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
    public function generateDevisPdf(Devis $devis, array $options = []): string
    {
        $this->company = $devis->company;
        
        $data = [
            'devis' => $devis,
            'company' => $this->company,
            'config' => $this->getPdfConfig(),
            'mentions_legales' => $this->getMentionsLegales(DocumentType::DEVIS),
            'is_bon_commande' => $devis->is_bon_commande,
        ];

        $template = $devis->template ?? $this->company->pdf_template_devis ?? 'default';
        $view = "pdf.devis.{$template}";

        if (!View::exists($view)) {
            $view = 'pdf.devis.default';
        }

        $pdf = Pdf::loadView($view, $data);
        
        // Configuration du PDF
        $pdf->setPaper($this->config['paper'] ?? 'A4', $this->config['orientation'] ?? 'portrait');
        
        // Options personnalisées
        if (isset($options['margins'])) {
            $pdf->setOptions([
                'margin_top' => $options['margins']['top'] ?? 20,
                'margin_right' => $options['margins']['right'] ?? 15,
                'margin_bottom' => $options['margins']['bottom'] ?? 20,
                'margin_left' => $options['margins']['left'] ?? 15,
            ]);
        }

        $filename = $this->generateFilename($devis->numero, DocumentType::DEVIS);
        $pdfContent = $pdf->output();

        // Sauvegarde du fichier
        $path = "documents/devis/{$this->company->id}/{$filename}";
        Storage::disk('local')->put($path, $pdfContent);

        return $path;
    }

    /**
     * Génère un PDF pour une facture
     */
    public function generateFacturePdf(Facture $facture, array $options = []): string
    {
        $this->company = $facture->company;
        
        $data = [
            'facture' => $facture,
            'company' => $this->company,
            'config' => $this->getPdfConfig(),
            'mentions_legales' => $this->getMentionsLegales(DocumentType::FACTURE),
        ];

        $template = $facture->template ?? $this->company->pdf_template_facture ?? 'default';
        $view = "pdf.facture.{$template}";

        if (!View::exists($view)) {
            $view = 'pdf.facture.default';
        }

        $pdf = Pdf::loadView($view, $data);
        $pdf->setPaper($this->config['paper'] ?? 'A4', $this->config['orientation'] ?? 'portrait');

        $filename = $this->generateFilename($facture->numero, DocumentType::FACTURE);
        $pdfContent = $pdf->output();

        $path = "documents/factures/{$this->company->id}/{$filename}";
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
            'logo' => $this->company->logo_path,
            'colors' => [
                'primary' => $this->company->pdf_color_primary ?? '#3B82F6',
                'secondary' => $this->company->pdf_color_secondary ?? '#6B7280',
                'accent' => $this->company->pdf_color_accent ?? '#10B981',
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
        ]);
    }

    /**
     * Récupère les mentions légales selon le type de document
     */
    protected function getMentionsLegales(DocumentType $type): array
    {
        $mentions = [];

        if (!$this->company) {
            return $mentions;
        }

        // Mentions communes
        $mentions['company_info'] = [
            'raison_sociale' => $this->company->raison_sociale,
            'forme_juridique' => $this->company->forme_juridique,
            'capital' => $this->company->capital,
            'siret' => $this->company->siret,
            'rcs' => $this->company->rcs,
            'code_ape' => $this->company->code_ape,
            'numero_tva' => $this->company->numero_tva,
        ];

        // Mentions spécifiques selon le type de document
        switch ($type) {
            case DocumentType::DEVIS:
                $mentions['validity'] = "Ce devis est valable {$this->company->devis_validite_jours} jours à compter de sa date d'émission.";
                $mentions['payment'] = $this->company->devis_conditions_paiement;
                
                if ($this->company->devis_mention_acompte) {
                    $mentions['acompte'] = "Un acompte de {$this->company->devis_pourcentage_acompte}% sera demandé à la commande.";
                }
                break;

            case DocumentType::FACTURE:
                $mentions['payment_terms'] = $this->company->facture_conditions_paiement;
                $mentions['late_penalties'] = $this->company->facture_penalites_retard;
                break;
        }

        // Mentions TVA
        if ($this->company->regime_tva === 'franchise') {
            $mentions['tva'] = "TVA non applicable, art. 293 B du CGI";
        }

        return $mentions;
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
}
