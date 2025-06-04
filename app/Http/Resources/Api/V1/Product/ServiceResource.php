<?php

namespace App\Http\Resources\Api\V1\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'unit_price' => $this->unit_price,
            'cost_price' => $this->cost_price,
            'vat_rate' => $this->vat_rate,
            'unit' => $this->unit,
            'duration' => $this->duration,
            'is_recurring' => $this->is_recurring,
            'recurring_period' => $this->recurring_period,
            'setup_fee' => $this->setup_fee,
            'is_active' => $this->is_active,
            'options' => $this->options,
            'margin' => $this->when($this->cost_price, function () {
                return $this->unit_price - $this->cost_price;
            }),
            'margin_percentage' => $this->when($this->cost_price && $this->cost_price > 0, function () {
                return round((($this->unit_price - $this->cost_price) / $this->cost_price) * 100, 2);
            }),
            'total_price_with_setup' => $this->when($this->setup_fee, function () {
                return $this->unit_price + $this->setup_fee;
            }),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
