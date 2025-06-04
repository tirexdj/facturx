<?php

namespace App\Actions\Api\V1\Product;

use App\Domain\Product\Models\Category;
use Illuminate\Support\Facades\DB;

class CreateCategoryAction
{
    public function execute(array $data): Category
    {
        return DB::transaction(function () use ($data) {
            $category = Category::create([
                'company_id' => $data['company_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'type' => $data['type'],
                'parent_id' => $data['parent_id'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
                'color' => $data['color'] ?? null,
                'icon' => $data['icon'] ?? null,
            ]);

            // Log de l'activité
            activity()
                ->performedOn($category)
                ->withProperties(['attributes' => $data])
                ->log('Catégorie créée');

            return $category;
        });
    }
}
