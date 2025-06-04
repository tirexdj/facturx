@extends('pdf.layout')

@section('document_title')
{{ $is_purchase_order ? 'Bon de commande' : 'Devis' }} {{ $quote->quote_number }}
@endsection

@section('content')
<!-- Titre du document -->
<div class="document-title">
    {{ $is_purchase_order ? 'BON DE COMMANDE' : 'DEVIS' }}
</div>

<!-- Informations du document et du client -->
<div class="document-info">
    <div class="document-details info-section">
        <h3>{{ $is_purchase_order ? 'Bon de commande' : 'Devis' }}</h3>
        <p><strong>Numéro :</strong> {{ $quote->quote_number }}</p>
        @if($quote->reference)
            <p><strong>Référence :</strong> {{ $quote->reference }}</p>
        @endif
        <p><strong>Date :</strong> {{ $quote->date->format('d/m/Y') }}</p>
        <p><strong>Validité :</strong> {{ $quote->validity_date->format('d/m/Y') }}</p>
        @if($quote->currency_code !== 'EUR')
            <p><strong>Devise :</strong> {{ $quote->currency_code }}</p>
        @endif
        
        @if($quote->status)
            <p><strong>Statut :</strong> 
                <span style="color: {{ $quote->status_color }};">{{ $quote->status_label }}</span>
            </p>
        @endif
    </div>

    <div class="client-details info-section">
        <h3>Client</h3>
        <p><strong>{{ $quote->client->name }}</strong></p>
        @if($quote->client->company)
            <p>{{ $quote->client->company }}</p>
        @endif
        <p>{{ $quote->client->address }}</p>
        <p>{{ $quote->client->postal_code }} {{ $quote->client->city }}</p>
        @if($quote->client->country && $quote->client->country !== 'France')
            <p>{{ $quote->client->country }}</p>
        @endif
        
        @if($quote->client->email)
            <p><strong>Email :</strong> {{ $quote->client->email }}</p>
        @endif
        @if($quote->client->phone)
            <p><strong>Tél :</strong> {{ $quote->client->phone }}</p>
        @endif
        @if($quote->client->vat_number)
            <p><strong>N° TVA :</strong> {{ $quote->client->vat_number }}</p>
        @endif
    </div>
</div>

<!-- Introduction/Objet -->
@if($quote->title || $quote->introduction)
    <div class="quote-introduction mb-20">
        @if($quote->title)
            <h3 style="color: {{ $config['colors']['primary'] }}; margin-bottom: 10px;">{{ $quote->title }}</h3>
        @endif
        @if($quote->introduction)
            <p style="font-style: italic; color: {{ $config['colors']['secondary'] }};">{{ $quote->introduction }}</p>
        @endif
    </div>
@endif

<!-- Lignes du devis -->
@if($quote->lines && $quote->lines->count() > 0)
    <table class="table">
        <thead>
            <tr>
                <th style="width: 45%;">Désignation</th>
                <th style="width: 10%; text-align: center;">Qté</th>
                <th style="width: 10%; text-align: center;">Unité</th>
                <th style="width: 15%; text-align: right;">Prix unit. HT</th>
                <th style="width: 10%; text-align: center;">TVA</th>
                <th style="width: 15%; text-align: right;">Total HT</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quote->lines->where('is_optional', false) as $line)
                <tr>
                    <td>
                        <strong>{{ $line->title }}</strong>
                        @if($line->description && ($config['body']['show_descriptions'] ?? true))
                            <br><small style="color: {{ $config['colors']['secondary'] }};">{{ $line->description }}</small>
                        @endif
                        @if($line->product && $config['body']['show_product_images'] && $line->product->hasMedia('images'))
                            <br><img src="{{ $line->product->getFirstMediaUrl('images') }}" style="max-width: 50px; max-height: 50px;">
                        @endif
                    </td>
                    <td class="text-center">{{ number_format($line->quantity, 2, ',', ' ') }}</td>
                    <td class="text-center">{{ $line->unit->name ?? 'unité' }}</td>
                    <td class="text-right">{{ number_format($line->unit_price_net, 2, ',', ' ') }} €</td>
                    <td class="text-center">{{ number_format($line->vatRate->rate ?? 0, 1) }}%</td>
                    <td class="text-right">{{ number_format($line->total_net, 2, ',', ' ') }} €</td>
                </tr>
            @endforeach

            <!-- Lignes optionnelles -->
            @if($quote->lines->where('is_optional', true)->count() > 0)
                <tr>
                    <td colspan="6" style="background-color: #f9fafb; font-weight: bold; color: {{ $config['colors']['secondary'] }}; text-align: center; padding: 8px;">
                        OPTIONS (non incluses dans le total)
                    </td>
                </tr>
                @foreach($quote->lines->where('is_optional', true) as $line)
                    <tr style="opacity: 0.7;">
                        <td>
                            <strong>{{ $line->title }}</strong> <em>(option)</em>
                            @if($line->description && ($config['body']['show_descriptions'] ?? true))
                                <br><small style="color: {{ $config['colors']['secondary'] }};">{{ $line->description }}</small>
                            @endif
                        </td>
                        <td class="text-center">{{ number_format($line->quantity, 2, ',', ' ') }}</td>
                        <td class="text-center">{{ $line->unit->name ?? 'unité' }}</td>
                        <td class="text-right">{{ number_format($line->unit_price_net, 2, ',', ' ') }} €</td>
                        <td class="text-center">{{ number_format($line->vatRate->rate ?? 0, 1) }}%</td>
                        <td class="text-right">{{ number_format($line->total_net, 2, ',', ' ') }} €</td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>
@endif

<!-- Totaux -->
<div class="totals">
    <table class="totals-table">
        <tr>
            <td class="label">Sous-total HT :</td>
            <td class="amount">{{ number_format($quote->subtotal_net, 2, ',', ' ') }} €</td>
        </tr>
        
        @if($quote->discount_amount > 0)
            <tr>
                <td class="label">
                    Remise 
                    @if($quote->discount_type === 'percentage')
                        ({{ number_format($quote->discount_value, 1) }}%)
                    @endif
                    :
                </td>
                <td class="amount">-{{ number_format($quote->discount_amount, 2, ',', ' ') }} €</td>
            </tr>
            <tr>
                <td class="label">Total HT après remise :</td>
                <td class="amount">{{ number_format($quote->total_net, 2, ',', ' ') }} €</td>
            </tr>
        @endif

        <tr>
            <td class="label">TVA :</td>
            <td class="amount">{{ number_format($quote->total_tax, 2, ',', ' ') }} €</td>
        </tr>
        
        <tr class="total-final">
            <td class="label">TOTAL TTC :</td>
            <td class="amount">{{ number_format($quote->total_gross, 2, ',', ' ') }} €</td>
        </tr>

        @if($quote->requiresDeposit())
            <tr style="border-top: 2px solid {{ $config['colors']['accent'] }};">
                <td class="label" style="color: {{ $config['colors']['accent'] }};">
                    Acompte à la commande 
                    @if($quote->deposit_percentage > 0)
                        ({{ number_format($quote->deposit_percentage, 1) }}%)
                    @endif
                    :
                </td>
                <td class="amount" style="color: {{ $config['colors']['accent'] }};">
                    {{ number_format($quote->deposit_amount, 2, ',', ' ') }} €
                </td>
            </tr>
        @endif
    </table>
</div>

<!-- Notes et conditions -->
@if($quote->notes)
    <div class="notes mt-20">
        <h4 style="color: {{ $config['colors']['primary'] }}; margin-bottom: 10px;">Notes :</h4>
        <p>{{ $quote->notes }}</p>
    </div>
@endif

@if($quote->payment_terms)
    <div class="payment-terms mt-20">
        <h4 style="color: {{ $config['colors']['primary'] }}; margin-bottom: 10px;">Conditions de paiement :</h4>
        <p>{{ $quote->payment_terms }}</p>
    </div>
@endif

@if($quote->terms)
    <div class="terms mt-20">
        <h4 style="color: {{ $config['colors']['primary'] }}; margin-bottom: 10px;">Conditions générales :</h4>
        <p>{{ $quote->terms }}</p>
    </div>
@endif

<!-- Mentions légales obligatoires -->
<div class="legal-mentions">
    <h4>Mentions légales</h4>
    
    @if($mentions_legales['validity'] ?? false)
        <p>{{ $mentions_legales['validity'] }}</p>
    @endif

    @if($mentions_legales['acceptance'] ?? false)
        <p>{{ $mentions_legales['acceptance'] }}</p>
    @endif

    @if($mentions_legales['deposit'] ?? false)
        <p>{{ $mentions_legales['deposit'] }}</p>
    @endif

    @if($mentions_legales['payment'] ?? false)
        <p><strong>Conditions de paiement :</strong> {{ $mentions_legales['payment'] }}</p>
    @endif

    @if($mentions_legales['vat'] ?? false)
        <p>{{ $mentions_legales['vat'] }}</p>
    @endif

    @if($mentions_legales['regulation'] ?? false)
        <p>{{ $mentions_legales['regulation'] }}</p>
    @endif

    @if($mentions_legales['insurance'] ?? false)
        <p>{{ $mentions_legales['insurance'] }}</p>
    @endif

    @if($is_purchase_order)
        @if($mentions_legales['order'] ?? false)
            <p><strong>{{ $mentions_legales['order'] }}</strong></p>
        @endif
        @if($mentions_legales['delivery'] ?? false)
            <p>{{ $mentions_legales['delivery'] }}</p>
        @endif
    @endif
</div>

<!-- Zone de signature pour acceptation -->
@if(!$is_purchase_order && !in_array($quote->status, [\App\Domain\Shared\Enums\QuoteStatus::ACCEPTED, \App\Domain\Shared\Enums\QuoteStatus::REJECTED]))
    <div style="margin-top: 40px; display: flex; justify-content: space-between;">
        <div style="width: 45%;">
            <p style="margin-bottom: 50px;"><strong>Signature du client</strong></p>
            <p style="border-bottom: 1px solid #000; height: 1px; margin-bottom: 5px;"></p>
            <p style="font-size: 10px; color: {{ $config['colors']['secondary'] }};">Date et signature précédées de la mention "Bon pour accord"</p>
        </div>
        
        <div style="width: 45%;">
            <p style="margin-bottom: 50px;"><strong>{{ $company->name }}</strong></p>
            <p style="border-bottom: 1px solid #000; height: 1px; margin-bottom: 5px;"></p>
            <p style="font-size: 10px; color: {{ $config['colors']['secondary'] }};">Cachet et signature</p>
        </div>
    </div>
@endif

<!-- Information si devis accepté avec signature -->
@if($quote->status === \App\Domain\Shared\Enums\QuoteStatus::ACCEPTED && $quote->accepted_at)
    <div style="margin-top: 30px; padding: 15px; background-color: #ecfdf5; border: 1px solid #10b981; border-radius: 5px;">
        <p style="color: #065f46; font-weight: bold;">
            ✓ Devis accepté le {{ $quote->accepted_at->format('d/m/Y à H:i') }}
        </p>
        @if($quote->signature_data)
            <p style="color: #065f46; font-size: 10px;">Signature électronique enregistrée</p>
        @endif
    </div>
@endif

<!-- Footer du document -->
@if($quote->footer)
    <div class="document-footer mt-20" style="text-align: center; font-size: 10px; color: {{ $config['colors']['secondary'] }};">
        {{ $quote->footer }}
    </div>
@endif
@endsection

@push('styles')
<style>
    /* Styles spécifiques au devis */
    .quote-introduction {
        background-color: #f9fafb;
        padding: 15px;
        border-left: 4px solid {{ $config['colors']['primary'] ?? '#3B82F6' }};
        border-radius: 0 5px 5px 0;
    }

    .optional-line {
        font-style: italic;
        opacity: 0.8;
    }

    .signature-section {
        page-break-inside: avoid;
    }

    .accepted-stamp {
        background-color: #ecfdf5;
        border: 2px solid #10b981;
        color: #065f46;
        transform: rotate(-5deg);
        padding: 10px 20px;
        font-weight: bold;
        position: absolute;
        right: 50px;
        top: 100px;
        font-size: 16px;
    }

    /* Impression spécifique */
    @media print {
        .signature-section {
            page-break-before: auto;
        }
        
        .document-footer {
            position: fixed;
            bottom: 0;
        }
    }
</style>
@endpush
