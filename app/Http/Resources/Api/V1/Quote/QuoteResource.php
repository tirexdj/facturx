<?php

namespace App\Http\Resources\Api\V1\Quote;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuoteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quote_number' => $this->quote_number,
            'quote_date' => $this->quote_date?->format('Y-m-d'),
            'valid_until' => $this->valid_until?->format('Y-m-d'),
            'subject' => $this->subject,
            'notes' => $this->notes,
            'terms' => $this->terms,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'total' => $this->total,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'shipping_amount' => $this->shipping_amount,
            'sent_at' => $this->sent_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            
            // Relations conditionnelles
            'customer' => $this->whenLoaded('customer', function () {
                return [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name,
                    'email' => $this->customer->email,
                    'phone' => $this->customer->phone,
                    'company_name' => $this->customer->company_name,
                    'siren' => $this->customer->siren,
                    'siret' => $this->customer->siret,
                ];
            }),
            
            'company' => $this->whenLoaded('company', function () {
                return [
                    'id' => $this->company->id,
                    'name' => $this->company->name,
                    'email' => $this->company->email,
                    'phone' => $this->company->phone,
                    'address' => $this->company->address,
                    'postal_code' => $this->company->postal_code,
                    'city' => $this->company->city,
                    'country' => $this->company->country,
                    'siren' => $this->company->siren,
                    'siret' => $this->company->siret,
                    'vat_number' => $this->company->vat_number,
                ];
            }),
            
            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'description' => $item->description,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'tax_rate' => $item->tax_rate,
                        'line_total' => $item->line_total,
                        'product' => $this->when($item->relationLoaded('product') && $item->product, [
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'reference' => $item->product->reference,
                            'price' => $item->product->price,
                            'tax_rate' => $item->product->tax_rate,
                        ]),
                    ];
                });
            }),
            
            'status_histories' => $this->whenLoaded('statusHistories', function () {
                return $this->statusHistories->map(function ($history) {
                    return [
                        'id' => $history->id,
                        'status' => $history->status,
                        'status_label' => $this->getStatusLabel($history->status),
                        'comment' => $history->comment,
                        'created_at' => $history->created_at?->format('Y-m-d H:i:s'),
                        'user' => $this->when($history->relationLoaded('user') && $history->user, [
                            'id' => $history->user->id,
                            'name' => $history->user->name,
                            'email' => $history->user->email,
                        ]),
                    ];
                });
            }),
            
            // URLs d'actions
            'actions' => [
                'view_pdf' => route('api.v1.quotes.pdf', $this->id),
                'download_pdf' => route('api.v1.quotes.pdf', ['quote' => $this->id, 'download' => true]),
                'send' => route('api.v1.quotes.send', $this->id),
                'duplicate' => route('api.v1.quotes.duplicate', $this->id),
                'convert' => $this->canConvert() ? route('api.v1.quotes.convert', $this->id) : null,
            ],
            
            // Statuts booléens pour l'interface
            'can_edit' => $this->canEdit(),
            'can_delete' => $this->canDelete(),
            'can_send' => $this->canSend(),
            'can_convert' => $this->canConvert(),
            'is_expired' => $this->isExpired(),
        ];
    }

    /**
     * Obtenir le libellé du statut
     */
    private function getStatusLabel(?string $status = null): string
    {
        $status = $status ?? $this->status;
        
        return match($status) {
            'draft' => 'Brouillon',
            'sent' => 'Envoyé',
            'pending' => 'En attente',
            'accepted' => 'Accepté',
            'declined' => 'Refusé',
            'expired' => 'Expiré',
            'converted' => 'Converti',
            default => 'Inconnu'
        };
    }

    /**
     * Vérifier si le devis peut être modifié
     */
    private function canEdit(): bool
    {
        return in_array($this->status, ['draft', 'sent', 'pending']);
    }

    /**
     * Vérifier si le devis peut être supprimé
     */
    private function canDelete(): bool
    {
        return !in_array($this->status, ['accepted', 'converted']);
    }

    /**
     * Vérifier si le devis peut être envoyé
     */
    private function canSend(): bool
    {
        return !in_array($this->status, ['expired', 'declined', 'converted']);
    }

    /**
     * Vérifier si le devis peut être converti
     */
    private function canConvert(): bool
    {
        return $this->status === 'accepted';
    }

    /**
     * Vérifier si le devis est expiré
     */
    private function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast() && $this->status !== 'accepted';
    }
}
