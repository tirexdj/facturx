<?php

namespace App\Http\Resources\Api\V1\Shared;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
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
            'label' => $this->label,
            'line_1' => $this->line_1,
            'line_2' => $this->line_2,
            'line_3' => $this->line_3,
            'postal_code' => $this->postal_code,
            'city' => $this->city,
            'state_province' => $this->state_province,
            'country_code' => $this->country_code,
            'is_default' => $this->is_default,
            'is_billing' => $this->is_billing,
            'is_shipping' => $this->is_shipping,
            'formatted_address' => $this->formatted_address,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Get the formatted address.
     */
    protected function getFormattedAddressAttribute(): string
    {
        $parts = array_filter([
            $this->line_1,
            $this->line_2,
            $this->line_3,
            $this->postal_code . ' ' . $this->city,
            $this->state_province,
            $this->country_code,
        ]);

        return implode(', ', $parts);
    }
}
