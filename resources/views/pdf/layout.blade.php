<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('document_title')</title>
    <style>
        /* Reset et styles de base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: {{ $config['fonts']['primary'] ?? 'DejaVu Sans' }}, sans-serif;
            font-size: {{ $config['body']['font_size'] ?? 12 }}px;
            line-height: {{ $config['body']['line_height'] ?? 1.4 }};
            color: {{ $config['colors']['text'] ?? '#1F2937' }};
            background-color: {{ $config['colors']['background'] ?? '#ffffff' }};
        }

        /* Layout principal */
        .document {
            width: 100%;
            max-width: 210mm;
            margin: 0 auto;
            background: white;
        }

        /* En-tête */
        .header {
            @if($config['header']['show'] ?? true)
                height: {{ $config['header']['height'] ?? 80 }}mm;
                background-color: {{ $config['header']['background_color'] ?? '#ffffff' }};
                padding: 20px;
                border-bottom: 2px solid {{ $config['colors']['primary'] ?? '#3B82F6' }};
            @else
                display: none;
            @endif
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            height: 100%;
        }

        .company-logo {
            max-height: 60px;
            max-width: 200px;
        }

        .company-info {
            text-align: right;
            font-size: 11px;
            line-height: 1.3;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: {{ $config['colors']['primary'] ?? '#3B82F6' }};
            margin-bottom: 8px;
        }

        /* Corps du document */
        .content {
            padding: 20px;
            min-height: 200mm;
        }

        /* Titre du document */
        .document-title {
            font-size: 24px;
            font-weight: bold;
            color: {{ $config['colors']['primary'] ?? '#3B82F6' }};
            margin-bottom: 30px;
            text-align: center;
        }

        /* Informations du document */
        .document-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .document-details, .client-details {
            width: 48%;
        }

        .info-section h3 {
            font-size: 14px;
            font-weight: bold;
            color: {{ $config['colors']['secondary'] ?? '#6B7280' }};
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-section p {
            margin-bottom: 5px;
            font-size: 11px;
        }

        /* Tableaux */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            @if($config['body']['table_style'] === 'bordered')
                border: 1px solid {{ $config['colors']['secondary'] ?? '#6B7280' }};
            @endif
        }

        .table th {
            background-color: {{ $config['colors']['primary'] ?? '#3B82F6' }};
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
            @if($config['body']['table_style'] === 'bordered')
                border: 1px solid {{ $config['colors']['primary'] ?? '#3B82F6' }};
            @endif
        }

        .table td {
            padding: 10px 8px;
            font-size: 10px;
            @if($config['body']['table_style'] === 'bordered')
                border: 1px solid {{ $config['colors']['secondary'] ?? '#6B7280' }};
            @elseif($config['body']['table_style'] === 'zebra')
                border-bottom: 1px solid #f3f4f6;
            @endif
        }

        @if($config['body']['table_style'] === 'zebra')
        .table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }
        @endif

        .table .text-right {
            text-align: right;
        }

        .table .text-center {
            text-align: center;
        }

        /* Totaux */
        .totals {
            width: 100%;
            margin-top: 30px;
        }

        .totals-table {
            width: 300px;
            margin-left: auto;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        .totals-table .label {
            text-align: left;
            font-weight: bold;
        }

        .totals-table .amount {
            text-align: right;
            font-weight: bold;
        }

        .total-final {
            background-color: {{ $config['colors']['primary'] ?? '#3B82F6' }};
            color: white;
            font-size: 14px;
        }

        /* Pied de page */
        .footer {
            @if($config['footer']['show'] ?? true)
                height: {{ $config['footer']['height'] ?? 50 }}mm;
                background-color: {{ $config['footer']['background_color'] ?? '#ffffff' }};
                padding: 20px;
                border-top: 1px solid {{ $config['colors']['secondary'] ?? '#6B7280' }};
                margin-top: 30px;
            @else
                display: none;
            @endif
        }

        /* Mentions légales */
        .legal-mentions {
            font-size: 9px;
            line-height: 1.3;
            color: {{ $config['colors']['secondary'] ?? '#6B7280' }};
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        .legal-mentions h4 {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 8px;
            color: {{ $config['colors']['text'] ?? '#1F2937' }};
        }

        .legal-mentions p {
            margin-bottom: 6px;
        }

        /* Utilitaires */
        .text-bold {
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .mb-10 {
            margin-bottom: 10px;
        }

        .mb-20 {
            margin-bottom: 20px;
        }

        .mt-20 {
            margin-top: 20px;
        }

        /* Responsive pour PDF */
        @page {
            margin: {{ $config['layout']['margins_top'] ?? 20 }}mm {{ $config['layout']['margins_right'] ?? 15 }}mm {{ $config['layout']['margins_bottom'] ?? 20 }}mm {{ $config['layout']['margins_left'] ?? 15 }}mm;
            size: {{ $config['layout']['paper_size'] ?? 'A4' }} {{ $config['layout']['orientation'] ?? 'portrait' }};
        }

        /* Filigrane si activé */
        @if($config['watermark']['show'] ?? false)
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 72px;
            color: rgba(0, 0, 0, {{ $config['watermark']['opacity'] ?? 0.1 }});
            z-index: -1;
            font-weight: bold;
        }
        @endif
    </style>
    @stack('styles')
</head>
<body>
    @if($config['watermark']['show'] ?? false)
        <div class="watermark">{{ $config['watermark']['text'] ?? 'CONFIDENTIEL' }}</div>
    @endif

    <div class="document">
        <!-- En-tête -->
        <div class="header">
            <div class="header-content">
                <div class="company-logo-section">
                    @if($config['logo'] && ($config['header']['show_logo'] ?? true))
                        <img src="{{ $config['logo'] }}" alt="Logo {{ $company->name }}" class="company-logo">
                    @endif
                </div>
                
                <div class="company-info">
                    @if($config['header']['show_company_info'] ?? true)
                        <div class="company-name">{{ $company->name }}</div>
                        <p>{{ $company->address }}</p>
                        <p>{{ $company->postal_code }} {{ $company->city }}</p>
                        @if($config['header']['show_contact_info'] ?? true)
                            @if($company->phone)
                                <p>Tél : {{ $company->phone }}</p>
                            @endif
                            @if($company->email)
                                <p>Email : {{ $company->email }}</p>
                            @endif
                            @if($company->website)
                                <p>Web : {{ $company->website }}</p>
                            @endif
                        @endif
                    @endif
                    
                    @if($config['header']['custom_content'])
                        <div class="custom-header-content">
                            {!! nl2br(e($config['header']['custom_content'])) !!}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Corps du document -->
        <div class="content">
            @yield('content')
        </div>

        <!-- Pied de page -->
        <div class="footer">
            @if($config['footer']['show_company_footer'] ?? true)
                <div class="footer-company-info">
                    @if($mentions_legales['company_info'])
                        <p>
                            {{ $mentions_legales['company_info']['name'] ?? $company->name }}
                            @if($mentions_legales['company_info']['legal_form'])
                                - {{ $mentions_legales['company_info']['legal_form'] }}
                            @endif
                            @if($mentions_legales['company_info']['capital'])
                                - Capital : {{ number_format($mentions_legales['company_info']['capital'], 2, ',', ' ') }} €
                            @endif
                        </p>
                        @if($mentions_legales['company_info']['siret'])
                            <p>SIRET : {{ $mentions_legales['company_info']['siret'] }}</p>
                        @endif
                        @if($mentions_legales['company_info']['vat_number'])
                            <p>N° TVA : {{ $mentions_legales['company_info']['vat_number'] }}</p>
                        @endif
                    @endif
                </div>
            @endif

            @if($config['footer']['show_page_numbers'] ?? true)
                <div class="page-numbers">
                    <script type="text/php">
                        if (isset($pdf)) {
                            $text = "Page {PAGE_NUM} sur {PAGE_COUNT}";
                            $font = $fontMetrics->get_font("DejaVu Sans", "normal");
                            $size = 9;
                            $pageText = $pdf->get_width() - 60;
                            $y = $pdf->get_height() - 35;
                            $pdf->text($pageText, $y, $text, $font, $size);
                        }
                    </script>
                </div>
            @endif

            @if($config['footer']['custom_content'])
                <div class="custom-footer-content">
                    {!! nl2br(e($config['footer']['custom_content'])) !!}
                </div>
            @endif
        </div>
    </div>

    @stack('scripts')
</body>
</html>
