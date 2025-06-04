<?php

namespace App\Actions\Api\V1\Product;

use App\Domain\Product\Models\Category;
use Illuminate\Support\Facades\DB;

class DeleteCategoryAction
{
    public function execute(Category $category): bool
    {
        return DB::transaction(function () use ($category) {
            // Vérifier si la catégorie a des sous-catégories
            $hasChildren = $category->children()->exists();
            
            if ($hasChildren) {
                throw new \Exception('Impossible de supprimer une catégorie qui contient des sous-catégories.');
            }

            // Vérifier si la catégorie contient des produits ou services
            $hasProducts = $category->products()->exists();
            $hasServices = $category->services()->exists();

            if ($hasProducts || $hasServices) {
                throw new \Exception('Impossible de supprimer une catégorie qui contient des produits ou services.');
            }

            // Log avant suppression
            activity()
                ->performedOn($category)
                ->withProperties(['attributes' => $category->toArray()])
                ->log('Catégorie supprimée');

            return $category->delete();
        });
    }
}
