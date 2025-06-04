<?php

namespace App\Actions\Api\V1\Product;

use App\Domain\Product\Models\Category;
use Illuminate\Support\Facades\DB;

class UpdateCategoryAction
{
    public function execute(Category $category, array $data): Category
    {
        return DB::transaction(function () use ($category, $data) {
            $oldAttributes = $category->toArray();

            $category->update([
                'name' => $data['name'] ?? $category->name,
                'description' => $data['description'] ?? $category->description,
                'type' => $data['type'] ?? $category->type,
                'parent_id' => $data['parent_id'] ?? $category->parent_id,
                'sort_order' => $data['sort_order'] ?? $category->sort_order,
                'color' => $data['color'] ?? $category->color,
                'icon' => $data['icon'] ?? $category->icon,
            ]);

            // Log de l'activité
            activity()
                ->performedOn($category)
                ->withProperties([
                    'old' => $oldAttributes,
                    'attributes' => $category->fresh()->toArray()
                ])
                ->log('Catégorie modifiée');

            return $category->fresh();
        });
    }
}
