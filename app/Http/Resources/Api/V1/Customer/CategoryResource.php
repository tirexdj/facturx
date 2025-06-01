<?php

namespace App\Http\Resources\Api\V1\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            'slug' => $this->slug,
            'type' => $this->type,
            'description' => $this->description,
            'color' => $this->color,
            'icon' => $this->icon,
            'position' => $this->position,
            
            // Relations
            'parent' => $this->whenLoaded('parent', function () {
                return new CategoryResource($this->parent);
            }),
            'children' => CategoryResource::collection($this->whenLoaded('children')),
            
            // Counts
            'clients_count' => $this->when(
                $this->type === 'client',
                fn() => $this->clients()->count()
            ),
            'products_count' => $this->when(
                $this->type === 'product',
                fn() => $this->products()->count()
            ),
            'services_count' => $this->when(
                $this->type === 'service',
                fn() => $this->services()->count()
            ),
            
            // Icon URL if it's a media file
            'icon_url' => $this->when(
                $this->hasMedia('icon'),
                fn() => $this->getFirstMediaUrl('icon')
            ),
            
            // Metadata
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
