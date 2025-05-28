<?php

namespace App\Http\Resources\Api\V1\Company;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
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
            'name' => $this->name,
            'legal_name' => $this->legal_name,
            'trading_name' => $this->trading_name,
            'siren' => $this->siren,
            'siret' => $this->siret,
            'vat_number' => $this->vat_number,
            'registration_number' => $this->registration_number,
            'legal_form' => $this->legal_form,
            'website' => $this->website,
            'logo_path' => $this->logo_path,
            'pdp_id' => $this->pdp_id,
            'vat_regime' => $this->vat_regime,
            'fiscal_year_start' => $this->fiscal_year_start?->format('Y-m-d'),
            'currency_code' => $this->currency_code,
            'language_code' => $this->language_code,
            'is_active' => $this->is_active,
            'trial_ends_at' => $this->trial_ends_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            
            // Relations
            'plan' => $this->whenLoaded('plan', function () {
                return new PlanResource($this->plan);
            }),
            
            'addresses' => $this->whenLoaded('addresses', function () {
                return $this->addresses->map(function ($address) {
                    return [
                        'id' => $address->id,
                        'type' => $address->type,
                        'line_1' => $address->line_1,
                        'line_2' => $address->line_2,
                        'city' => $address->city,
                        'postal_code' => $address->postal_code,
                        'state' => $address->state,
                        'country_code' => $address->country_code,
                        'is_primary' => $address->is_primary,
                    ];
                });
            }),
            
            'phone_numbers' => $this->whenLoaded('phoneNumbers', function () {
                return $this->phoneNumbers->map(function ($phone) {
                    return [
                        'id' => $phone->id,
                        'number' => $phone->number,
                        'type' => $phone->type,
                        'country_code' => $phone->country_code,
                        'is_primary' => $phone->is_primary,
                    ];
                });
            }),
            
            'emails' => $this->whenLoaded('emails', function () {
                return $this->emails->map(function ($email) {
                    return [
                        'id' => $email->id,
                        'email' => $email->email,
                        'type' => $email->type,
                        'is_primary' => $email->is_primary,
                    ];
                });
            }),
            
            'users' => $this->whenLoaded('users', function () {
                return $this->users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'created_at' => $user->created_at?->format('Y-m-d H:i:s'),
                    ];
                });
            }),
            
            // Statistiques (si nécessaire)
            'stats' => $this->when($request->get('include_stats'), function () {
                return [
                    'users_count' => $this->users()->count(),
                    'clients_count' => $this->clients()->count(),
                    'products_count' => $this->products()->count(),
                    'services_count' => $this->services()->count(),
                    'quotes_count' => $this->quotes()->count(),
                    'invoices_count' => $this->invoices()->count(),
                ];
            }),
        ];
    }
}
