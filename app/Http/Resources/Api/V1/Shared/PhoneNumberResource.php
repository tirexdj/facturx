<?php

namespace App\Http\Resources\Api\V1\Shared;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PhoneNumberResource extends JsonResource
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
            'country_code' => $this->country_code,
            'number' => $this->number,
            'extension' => $this->extension,
            'formatted_number' => $this->formatted_number,
            'is_default' => $this->is_default,
            'is_mobile' => $this->is_mobile,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Get the formatted phone number.
     */
    protected function getFormattedNumberAttribute(): string
    {
        $formatted = $this->country_code . ' ' . $this->number;
        
        if ($this->extension) {
            $formatted .= ' ext. ' . $this->extension;
        }
        
        return $formatted;
    }
}
