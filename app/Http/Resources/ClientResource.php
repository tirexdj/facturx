<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'website' => $this->website,
            
            // Informations légales
            'siren' => $this->siren,
            'siret' => $this->siret,
            'vat_number' => $this->vat_number,
            'legal_form' => $this->legal_form,
            'capital' => $this->capital,
            'registration_number' => $this->registration_number,
            
            // Catégorisation
            'category' => $this->whenLoaded('category', function () {
                return new ClientCategoryResource($this->category);
            }),
            'tags' => $this->tags,
            
            // Status et notes
            'status' => $this->status,
            'notes' => $this->notes,
            
            // Informations commerciales
            'payment_terms' => $this->payment_terms,
            'payment_method' => $this->payment_method,
            'discount_rate' => $this->discount_rate,
            'credit_limit' => $this->credit_limit,
            
            // Statistiques
            'total_invoices' => $this->whenCounted('invoices'),
            'total_revenue' => $this->when(
                isset($this->total_revenue),
                $this->total_revenue
            ),
            'last_invoice_date' => $this->when(
                isset($this->last_invoice_date),
                $this->last_invoice_date?->format('Y-m-d')
            ),
            
            // Relations
            'addresses' => ClientAddressResource::collection($this->whenLoaded('addresses')),
            'contacts' => ClientContactResource::collection($this->whenLoaded('contacts')),
            'interactions' => ClientInteractionResource::collection($this->whenLoaded('interactions')),
            
            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'converted_at' => $this->when(
                $this->type === 'client' && $this->converted_at,
                $this->converted_at?->toISOString()
            ),
        ];
    }
}
