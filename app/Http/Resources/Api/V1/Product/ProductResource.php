<?php

namespace App\Http\Resources\Api\V1\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'reference' => $this->reference,
            'unit_price' => $this->unit_price,
            'cost_price' => $this->cost_price,
            'vat_rate' => $this->vat_rate,
            'unit' => $this->unit,
            'weight' => $this->weight,
            'dimensions' => $this->dimensions,
            'barcode' => $this->barcode,
            'stock_quantity' => $this->stock_quantity,
            'stock_alert_threshold' => $this->stock_alert_threshold,
            'is_active' => $this->is_active,
            'attributes' => $this->attributes,
            'variants' => $this->variants,
            'margin' => $this->when($this->cost_price, function () {
                return $this->unit_price - $this->cost_price;
            }),
            'margin_percentage' => $this->when($this->cost_price && $this->cost_price > 0, function () {
                return round((($this->unit_price - $this->cost_price) / $this->cost_price) * 100, 2);
            }),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
