<?php

namespace App\Actions\Api\V1\Customer;

use App\Domain\Customer\Models\Category;
use Exception;

class DeleteCategoryAction
{
    /**
     * Execute the action to delete a category.
     */
    public function execute(Category $category): bool
    {
        // Check if category has clients, products, or services
        $hasClients = $category->clients()->exists();
        $hasProducts = $category->products()->exists();
        $hasServices = $category->services()->exists();
        
        if ($hasClients || $hasProducts || $hasServices) {
            throw new Exception(
                'Impossible de supprimer cette catégorie car elle contient des éléments associés.',
                422
            );
        }
        
        // Check if category has children
        if ($category->children()->exists()) {
            throw new Exception(
                'Impossible de supprimer cette catégorie car elle a des sous-catégories.',
                422
            );
        }
        
        return $category->delete();
    }

    /**
     * Force delete a category and reassign children to parent.
     */
    public function forceDelete(Category $category): bool
    {
        // Move children to parent or set parent_id to null
        $category->children()->update(['parent_id' => $category->parent_id]);
        
        // Force delete the category
        return $category->forceDelete();
    }
}
