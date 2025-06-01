<?php

namespace App\Http\Resources\Api\V1\Customer;

use App\Http\Resources\Api\V1\Shared\AddressResource;
use App\Http\Resources\Api\V1\Shared\ContactResource;
use App\Http\Resources\Api\V1\Shared\EmailResource;
use App\Http\Resources\Api\V1\Shared\PhoneNumberResource;
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
            'client_type' => $this->client_type,
            'name' => $this->name,
            'legal_name' => $this->legal_name,
            'trading_name' => $this->trading_name,
            'siren' => $this->siren,
            'siret' => $this->siret,
            'vat_number' => $this->vat_number,
            'registration_number' => $this->registration_number,
            'legal_form' => $this->legal_form,
            'website' => $this->website,
            'currency_code' => $this->currency_code,
            'language_code' => $this->language_code,
            'credit_limit' => $this->credit_limit ? (float) $this->credit_limit : null,
            'notes' => $this->notes,
            'tags' => $this->tags,
            'has_overdue_invoices' => $this->hasOverdueInvoices(),
            
            // Relations
            'category' => $this->whenLoaded('category', function () {
                return new CategoryResource($this->category);
            }),
            'payment_terms' => $this->whenLoaded('paymentTerms', function () {
                return [
                    'id' => $this->paymentTerms->id,
                    'name' => $this->paymentTerms->name,
                    'days' => $this->paymentTerms->days,
                ];
            }),
            'addresses' => AddressResource::collection($this->whenLoaded('addresses')),
            'phone_numbers' => PhoneNumberResource::collection($this->whenLoaded('phoneNumbers')),
            'emails' => EmailResource::collection($this->whenLoaded('emails')),
            'contacts' => ContactResource::collection($this->whenLoaded('contacts')),
            
            // Computed fields
            'default_billing_address' => $this->when(
                $this->relationLoaded('addresses'),
                function () {
                    return $this->default_billing_address ? new AddressResource($this->default_billing_address) : null;
                }
            ),
            'default_shipping_address' => $this->when(
                $this->relationLoaded('addresses'),
                function () {
                    return $this->default_shipping_address ? new AddressResource($this->default_shipping_address) : null;
                }
            ),
            'primary_contact' => $this->when(
                $this->relationLoaded('contacts'),
                function () {
                    return $this->primary_contact ? new ContactResource($this->primary_contact) : null;
                }
            ),
            
            // Statistics (when requested)
            'statistics' => $this->when($request->has('include_statistics'), function () {
                return [
                    'total_quotes' => $this->quotes()->count(),
                    'total_invoices' => $this->invoices()->count(),
                    'total_revenue' => $this->invoices()->where('status', 'paid')->sum('total_gross'),
                    'outstanding_amount' => $this->invoices()->whereIn('status', ['sent', 'partial', 'overdue'])->sum('amount_due'),
                ];
            }),
            
            // Logo
            'logo_url' => $this->when(
                $this->hasMedia('logo'),
                fn() => $this->getFirstMediaUrl('logo')
            ),
            
            // Metadata
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
