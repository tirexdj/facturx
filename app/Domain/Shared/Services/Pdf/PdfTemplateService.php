<?php

namespace App\Domain\Shared\Services\Pdf;

use App\Domain\Company\Models\Company;
use App\Domain\Shared\Enums\DocumentType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

class PdfTemplateService
{
    protected array $availableTemplates = [
        'modern' => 'Moderne',
        'classic' => 'Classique',
        'minimal' => 'Minimaliste',
        'corporate' => 'Corporate',
        'creative' => 'Créatif',
    ];

    protected array $templateFields = [
        'header' => [
            'show_logo' => 'boolean',
            'show_company_info' => 'boolean',
            'show_contact_info' => 'boolean',
            'custom_content' => 'text',
            'height' => 'number',
            'background_color' => 'color',
        ],
        'body' => [
            'font_family' => 'select',
            'font_size' => 'number',
            'line_height' => 'number',
            'show_product_images' => 'boolean',
            'show_descriptions' => 'boolean',
            'table_style' => 'select',
            'border_style' => 'select',
        ],
        'footer' => [
            'show_page_numbers' => 'boolean',
            'show_company_footer' => 'boolean',
            'custom_content' => 'text',
            'height' => 'number',
            'background_color' => 'color',
        ],
        'colors' => [
            'primary' => 'color',
            'secondary' => 'color',
            'accent' => 'color',
            'text' => 'color',
            'background' => 'color',
        ],
        'layout' => [
            'margins_top' => 'number',
            'margins_bottom' => 'number',
            'margins_left' => 'number',
            'margins_right' => 'number',
            'paper_size' => 'select',
            'orientation' => 'select',
        ]
    ];

    /**
     * Récupère les templates disponibles pour un type de document
     */
    public function getAvailableTemplates(DocumentType $type): Collection
    {
        return collect($this->availableTemplates)->map(function ($label, $key) use ($type) {
            return [
                'key' => $key,
                'label' => $label,
                'preview_image' => $this->getTemplatePreviewPath($key, $type),
                'supports_customization' => $this->supportsCustomization($key),
            ];
        });
    }

    /**
     * Récupère la configuration d'un template pour une entreprise
     */
    public function getTemplateConfig(Company $company, DocumentType $type): array
    {
        $configField = $this->getConfigFieldName($type);
        $config = $company->{$configField} ?? [];

        return array_merge($this->getDefaultConfig($type), $config);
    }

    /**
     * Sauvegarde la configuration d'un template
     */
    public function saveTemplateConfig(Company $company, DocumentType $type, array $config): bool
    {
        $configField = $this->getConfigFieldName($type);
        $validatedConfig = $this->validateConfig($config);

        return $company->update([
            $configField => $validatedConfig
        ]);
    }

    /**
     * Génère un aperçu du template
     */
    public function generatePreview(Company $company, DocumentType $type, array $config = []): string
    {
        $templateConfig = array_merge(
            $this->getTemplateConfig($company, $type),
            $config
        );

        $sampleData = $this->generateSampleData($company, $type);
        
        return View::make('pdf.preview.' . $type->template(), [
            'config' => $templateConfig,
            'sample' => $sampleData,
            'company' => $company,
        ])->render();
    }

    /**
     * Valide une configuration de template
     */
    protected function validateConfig(array $config): array
    {
        $validated = [];

        foreach ($this->templateFields as $section => $fields) {
            foreach ($fields as $field => $type) {
                if (isset($config[$section][$field])) {
                    $validated[$section][$field] = $this->validateField(
                        $config[$section][$field],
                        $type
                    );
                }
            }
        }

        return $validated;
    }

    /**
     * Valide un champ selon son type
     */
    protected function validateField($value, string $type)
    {
        return match($type) {
            'boolean' => (bool) $value,
            'number' => (int) $value,
            'color' => $this->validateColor($value),
            'select' => (string) $value,
            'text' => (string) $value,
            default => $value,
        };
    }

    /**
     * Valide une couleur hexadécimale
     */
    protected function validateColor(string $color): string
    {
        if (preg_match('/^#[a-f0-9]{6}$/i', $color)) {
            return $color;
        }
        
        return '#000000'; // Couleur par défaut
    }

    /**
     * Récupère la configuration par défaut
     */
    protected function getDefaultConfig(DocumentType $type): array
    {
        return [
            'header' => [
                'show_logo' => true,
                'show_company_info' => true,
                'show_contact_info' => true,
                'height' => 80,
                'background_color' => '#ffffff',
            ],
            'body' => [
                'font_family' => 'DejaVu Sans',
                'font_size' => 12,
                'line_height' => 1.4,
                'show_product_images' => false,
                'show_descriptions' => true,
                'table_style' => 'bordered',
                'border_style' => 'solid',
            ],
            'footer' => [
                'show_page_numbers' => true,
                'show_company_footer' => true,
                'height' => 50,
                'background_color' => '#ffffff',
            ],
            'colors' => [
                'primary' => '#3B82F6',
                'secondary' => '#6B7280',
                'accent' => '#10B981',
                'text' => '#1F2937',
                'background' => '#ffffff',
            ],
            'layout' => [
                'margins_top' => 20,
                'margins_bottom' => 20,
                'margins_left' => 15,
                'margins_right' => 15,
                'paper_size' => 'A4',
                'orientation' => 'portrait',
            ],
        ];
    }

    /**
     * Génère des données d'exemple pour l'aperçu
     */
    protected function generateSampleData(Company $company, DocumentType $type): array
    {
        return match($type) {
            DocumentType::QUOTE => [
                'number' => 'DEV-2024-00001',
                'date' => now(),
                'validity_date' => now()->addDays(30),
                'client' => [
                    'name' => 'Client Exemple SAS',
                    'address' => '123 Rue de la Paix, 75001 Paris',
                    'email' => 'contact@client-exemple.fr',
                ],
                'lines' => [
                    [
                        'title' => 'Prestation de conseil',
                        'description' => 'Analyse et recommandations stratégiques',
                        'quantity' => 5,
                        'unit_price' => 150.00,
                        'total' => 750.00,
                    ],
                    [
                        'title' => 'Formation équipe',
                        'description' => 'Formation de 2 jours pour 10 personnes',
                        'quantity' => 1,
                        'unit_price' => 2500.00,
                        'total' => 2500.00,
                    ],
                ],
                'total_net' => 3250.00,
                'total_tax' => 650.00,
                'total_gross' => 3900.00,
            ],
            DocumentType::INVOICE => [
                'number' => 'FAC-2024-00001',
                'date' => now(),
                'due_date' => now()->addDays(30),
                'client' => [
                    'name' => 'Client Exemple SAS',
                    'address' => '123 Rue de la Paix, 75001 Paris',
                    'email' => 'contact@client-exemple.fr',
                ],
                'lines' => [
                    [
                        'title' => 'Produit exemple',
                        'description' => 'Description du produit vendu',
                        'quantity' => 2,
                        'unit_price' => 99.99,
                        'total' => 199.98,
                    ],
                ],
                'total_net' => 199.98,
                'total_tax' => 39.99,
                'total_gross' => 239.97,
            ],
            default => [],
        };
    }

    /**
     * Récupère le nom du champ de configuration
     */
    protected function getConfigFieldName(DocumentType $type): string
    {
        return match($type) {
            DocumentType::QUOTE => 'pdf_config_quote',
            DocumentType::PURCHASE_ORDER => 'pdf_config_purchase_order',
            DocumentType::INVOICE => 'pdf_config_invoice',
            DocumentType::CREDIT_NOTE => 'pdf_config_credit_note',
            default => 'pdf_config_default',
        };
    }

    /**
     * Récupère le chemin de l'image d'aperçu d'un template
     */
    protected function getTemplatePreviewPath(string $template, DocumentType $type): string
    {
        return "images/templates/{$type->value}/{$template}.png";
    }

    /**
     * Vérifie si un template supporte la personnalisation
     */
    protected function supportsCustomization(string $template): bool
    {
        return in_array($template, ['modern', 'classic', 'minimal']);
    }

    /**
     * Récupère les champs personnalisables pour l'interface
     */
    public function getCustomizableFields(): array
    {
        return [
            'header' => [
                'title' => 'En-tête',
                'fields' => [
                    'show_logo' => ['type' => 'boolean', 'label' => 'Afficher le logo'],
                    'show_company_info' => ['type' => 'boolean', 'label' => 'Afficher les informations société'],
                    'show_contact_info' => ['type' => 'boolean', 'label' => 'Afficher les informations de contact'],
                    'height' => ['type' => 'number', 'label' => 'Hauteur (mm)', 'min' => 50, 'max' => 150],
                    'background_color' => ['type' => 'color', 'label' => 'Couleur de fond'],
                    'custom_content' => ['type' => 'textarea', 'label' => 'Contenu personnalisé'],
                ]
            ],
            'body' => [
                'title' => 'Corps du document',
                'fields' => [
                    'font_family' => [
                        'type' => 'select',
                        'label' => 'Police',
                        'options' => [
                            'DejaVu Sans' => 'DejaVu Sans',
                            'DejaVu Serif' => 'DejaVu Serif',
                            'Arial' => 'Arial',
                            'Times New Roman' => 'Times New Roman',
                        ]
                    ],
                    'font_size' => ['type' => 'number', 'label' => 'Taille de police', 'min' => 8, 'max' => 16],
                    'line_height' => ['type' => 'number', 'label' => 'Hauteur de ligne', 'min' => 1.0, 'max' => 2.0, 'step' => 0.1],
                    'show_product_images' => ['type' => 'boolean', 'label' => 'Afficher les images produits'],
                    'show_descriptions' => ['type' => 'boolean', 'label' => 'Afficher les descriptions'],
                    'table_style' => [
                        'type' => 'select',
                        'label' => 'Style de tableau',
                        'options' => [
                            'bordered' => 'Avec bordures',
                            'borderless' => 'Sans bordures',
                            'zebra' => 'Lignes alternées',
                        ]
                    ],
                ]
            ],
            'footer' => [
                'title' => 'Pied de page',
                'fields' => [
                    'show_page_numbers' => ['type' => 'boolean', 'label' => 'Afficher la numérotation'],
                    'show_company_footer' => ['type' => 'boolean', 'label' => 'Afficher le pied de page société'],
                    'height' => ['type' => 'number', 'label' => 'Hauteur (mm)', 'min' => 30, 'max' => 100],
                    'background_color' => ['type' => 'color', 'label' => 'Couleur de fond'],
                    'custom_content' => ['type' => 'textarea', 'label' => 'Contenu personnalisé'],
                ]
            ],
            'colors' => [
                'title' => 'Couleurs',
                'fields' => [
                    'primary' => ['type' => 'color', 'label' => 'Couleur principale'],
                    'secondary' => ['type' => 'color', 'label' => 'Couleur secondaire'],
                    'accent' => ['type' => 'color', 'label' => 'Couleur d\'accent'],
                    'text' => ['type' => 'color', 'label' => 'Couleur du texte'],
                    'background' => ['type' => 'color', 'label' => 'Couleur de fond'],
                ]
            ],
            'layout' => [
                'title' => 'Mise en page',
                'fields' => [
                    'margins_top' => ['type' => 'number', 'label' => 'Marge haute (mm)', 'min' => 10, 'max' => 50],
                    'margins_bottom' => ['type' => 'number', 'label' => 'Marge basse (mm)', 'min' => 10, 'max' => 50],
                    'margins_left' => ['type' => 'number', 'label' => 'Marge gauche (mm)', 'min' => 10, 'max' => 50],
                    'margins_right' => ['type' => 'number', 'label' => 'Marge droite (mm)', 'min' => 10, 'max' => 50],
                    'paper_size' => [
                        'type' => 'select',
                        'label' => 'Format papier',
                        'options' => [
                            'A4' => 'A4',
                            'A3' => 'A3',
                            'Letter' => 'Letter',
                            'Legal' => 'Legal',
                        ]
                    ],
                    'orientation' => [
                        'type' => 'select',
                        'label' => 'Orientation',
                        'options' => [
                            'portrait' => 'Portrait',
                            'landscape' => 'Paysage',
                        ]
                    ],
                ]
            ],
        ];
    }

    /**
     * Duplique un template existant
     */
    public function duplicateTemplate(Company $company, DocumentType $fromType, DocumentType $toType): bool
    {
        $sourceConfig = $this->getTemplateConfig($company, $fromType);
        return $this->saveTemplateConfig($company, $toType, $sourceConfig);
    }

    /**
     * Remet un template aux valeurs par défaut
     */
    public function resetTemplate(Company $company, DocumentType $type): bool
    {
        $configField = $this->getConfigFieldName($type);
        return $company->update([$configField => null]);
    }
}
