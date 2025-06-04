<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration PDF par défaut
    |--------------------------------------------------------------------------
    |
    | Configuration par défaut pour la génération des PDFs.
    | Ces valeurs peuvent être surchargées par les paramètres de l'entreprise.
    |
    */

    'default' => [
        // Format et orientation
        'paper' => 'A4',
        'orientation' => 'portrait',
        
        // Marges (en mm)
        'margins' => [
            'top' => 20,
            'right' => 15,
            'bottom' => 20,
            'left' => 15,
        ],

        // Polices
        'fonts' => [
            'primary' => 'DejaVu Sans',
            'secondary' => 'DejaVu Sans',
        ],

        // Couleurs par défaut
        'colors' => [
            'primary' => '#3B82F6',
            'secondary' => '#6B7280',
            'accent' => '#10B981',
            'text' => '#1F2937',
            'background' => '#ffffff',
        ],

        // En-tête
        'header' => [
            'show' => true,
            'height' => 80,
            'show_logo' => true,
            'show_company_info' => true,
            'show_contact_info' => true,
            'background_color' => '#ffffff',
            'custom_content' => null,
        ],

        // Corps du document
        'body' => [
            'font_size' => 12,
            'line_height' => 1.4,
            'show_product_images' => false,
            'show_descriptions' => true,
            'table_style' => 'bordered', // bordered, borderless, zebra
            'border_style' => 'solid',
        ],

        // Pied de page
        'footer' => [
            'show' => true,
            'height' => 50,
            'show_page_numbers' => true,
            'show_company_footer' => true,
            'background_color' => '#ffffff',
            'custom_content' => null,
        ],

        // Filigrane
        'watermark' => [
            'show' => false,
            'text' => 'CONFIDENTIEL',
            'opacity' => 0.1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Templates disponibles
    |--------------------------------------------------------------------------
    */

    'templates' => [
        'quote' => [
            'modern' => 'Moderne',
            'classic' => 'Classique',
            'minimal' => 'Minimaliste',
            'corporate' => 'Corporate',
            'creative' => 'Créatif',
        ],
        'invoice' => [
            'modern' => 'Moderne',
            'classic' => 'Classique',
            'minimal' => 'Minimaliste',
            'corporate' => 'Corporate',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Options DomPDF
    |--------------------------------------------------------------------------
    */

    'options' => [
        'dpi' => 150,
        'defaultFont' => 'DejaVu Sans',
        'isRemoteEnabled' => true,
        'isHtml5ParserEnabled' => true,
        'isFontSubsettingEnabled' => true,
        'isPhpEnabled' => true,
        'chroot' => public_path(),
    ],

    /*
    |--------------------------------------------------------------------------
    | Chemins de stockage
    |--------------------------------------------------------------------------
    */

    'storage' => [
        'quotes' => 'documents/quotes',
        'invoices' => 'documents/invoices',
        'templates' => 'templates',
        'temp' => 'temp/pdf',
    ],

    /*
    |--------------------------------------------------------------------------
    | Formats de papier supportés
    |--------------------------------------------------------------------------
    */

    'paper_sizes' => [
        'A4' => 'A4 (210 × 297 mm)',
        'A3' => 'A3 (297 × 420 mm)',
        'Letter' => 'Letter (216 × 279 mm)',
        'Legal' => 'Legal (216 × 356 mm)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Styles de tableau
    |--------------------------------------------------------------------------
    */

    'table_styles' => [
        'bordered' => 'Avec bordures',
        'borderless' => 'Sans bordures',
        'zebra' => 'Lignes alternées',
        'minimal' => 'Minimal',
    ],

    /*
    |--------------------------------------------------------------------------
    | Polices disponibles
    |--------------------------------------------------------------------------
    */

    'fonts' => [
        'DejaVu Sans' => 'DejaVu Sans',
        'DejaVu Serif' => 'DejaVu Serif',
        'DejaVu Sans Mono' => 'DejaVu Sans Mono',
    ],

    /*
    |--------------------------------------------------------------------------
    | Mentions légales par défaut
    |--------------------------------------------------------------------------
    */

    'legal_mentions' => [
        'quote' => [
            'validity' => 'Ce devis est valable 30 jours à compter de sa date d\'émission.',
            'acceptance' => 'Pour accepter ce devis, veuillez le signer et le retourner avec la mention "Bon pour accord".',
            'payment' => 'Conditions de paiement selon les conditions générales de vente.',
        ],
        'invoice' => [
            'payment_terms' => 'Paiement à 30 jours fin de mois.',
            'late_penalties' => 'En cas de retard de paiement, des pénalités de 3 fois le taux d\'intérêt légal seront appliquées.',
            'recovery_fee' => 'Indemnité forfaitaire de recouvrement : 40€ (art. L441-10 du Code de commerce).',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Qualité et performance
    |--------------------------------------------------------------------------
    */

    'quality' => [
        'image_dpi' => 150,
        'compression' => true,
        'optimize_fonts' => true,
        'cache_enabled' => true,
        'cache_ttl' => 3600, // 1 heure
    ],

    /*
    |--------------------------------------------------------------------------
    | Sécurité
    |--------------------------------------------------------------------------
    */

    'security' => [
        'max_file_size' => 10 * 1024 * 1024, // 10 MB
        'allowed_extensions' => ['pdf'],
        'scan_uploads' => true,
        'encrypt_pdfs' => false,
    ],
];
