<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $quote->quote_number }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .company-logo {
            max-width: 200px;
            margin-bottom: 10px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
            margin: 0;
        }
        .company-info {
            font-size: 14px;
            color: #6b7280;
            margin: 5px 0;
        }
        .quote-info {
            background-color: #f8fafc;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .quote-number {
            font-size: 20px;
            font-weight: bold;
            color: #3b82f6;
            margin-bottom: 10px;
        }
        .quote-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            font-size: 14px;
        }
        .quote-details span {
            font-weight: 600;
        }
        .message-content {
            white-space: pre-line;
            margin: 20px 0;
            padding: 20px;
            background-color: #f9fafb;
            border-left: 4px solid #3b82f6;
            border-radius: 0 6px 6px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #6b7280;
        }
        .footer p {
            margin: 5px 0;
        }
        .cta-button {
            display: inline-block;
            background-color: #3b82f6;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: 600;
        }
        .cta-button:hover {
            background-color: #2563eb;
        }
        .highlight {
            background-color: #fef3c7;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: 600;
        }
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            .container {
                padding: 20px;
            }
            .quote-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- En-tête de l'entreprise -->
        <div class="header">
            @if($company->logo)
                <img src="{{ $company->logo }}" alt="{{ $company->name }}" class="company-logo">
            @endif
            <h1 class="company-name">{{ $company->name }}</h1>
            @if($company->address)
                <p class="company-info">{{ $company->address }}</p>
            @endif
            @if($company->postal_code || $company->city)
                <p class="company-info">{{ $company->postal_code }} {{ $company->city }}</p>
            @endif
            @if($company->phone)
                <p class="company-info">Tél : {{ $company->phone }}</p>
            @endif
            @if($company->email)
                <p class="company-info">Email : {{ $company->email }}</p>
            @endif
            @if($company->siren)
                <p class="company-info">SIREN : {{ $company->siren }}</p>
            @endif
        </div>

        <!-- Informations du devis -->
        <div class="quote-info">
            <div class="quote-number">
                Devis {{ $quote->quote_number }}
            </div>
            <div class="quote-details">
                <div>Date d'émission : <span>{{ $quote->quote_date->format('d/m/Y') }}</span></div>
                <div>Valable jusqu'au : <span class="highlight">{{ $quote->valid_until->format('d/m/Y') }}</span></div>
                <div>Destinataire : <span>{{ $customer->name }}</span></div>
                <div>Montant total : <span>{{ number_format($quote->total, 2, ',', ' ') }} €</span></div>
            </div>
        </div>

        <!-- Message personnalisé -->
        <div class="message-content">
            {{ $message }}
        </div>

        <!-- Informations importantes -->
        <div style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 15px; margin: 20px 0;">
            <p style="margin: 0; font-size: 14px; color: #dc2626;">
                <strong>Important :</strong> Ce devis est valable jusqu'au {{ $quote->valid_until->format('d/m/Y') }}. 
                Au-delà de cette date, les prix et conditions pourront être modifiés.
            </p>
        </div>

        <!-- Pied de page -->
        <div class="footer">
            <p>Ce devis a été généré automatiquement par {{ $company->name }}</p>
            <p>Pour toute question, n'hésitez pas à nous contacter</p>
            @if($company->website)
                <p>Site web : <a href="{{ $company->website }}">{{ $company->website }}</a></p>
            @endif
            <p style="margin-top: 15px; font-style: italic;">
                Merci de votre confiance !
            </p>
        </div>
    </div>
</body>
</html>
