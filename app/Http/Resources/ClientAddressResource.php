<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientAddressResource extends JsonResource
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
            'line1' => $this->line1,
            'line2' => $this->line2,
            'line3' => $this->line3,
            'postal_code' => $this->postal_code,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'is_default' => $this->is_default,
            'formatted_address' => $this->formatted_address,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
