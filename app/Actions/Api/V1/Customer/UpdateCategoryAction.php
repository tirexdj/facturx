<?php

namespace App\Actions\Api\V1\Customer;

use App\Domain\Customer\Models\Category;
use Illuminate\Support\Str;

class UpdateCategoryAction
{
    /**
     * Execute the action to update a category.
     */
    public function execute(Category $category, array $data): Category
    {
        // Generate slug if name changed and no explicit slug provided
        if (isset($data['name']) && $data['name'] !== $category->name) {
            if (!isset($data['slug']) || empty($data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug(
                    $data['name'], 
                    $category->company_id, 
                    $category->type,
                    $category->id
                );
            }
        }

        $category->update($data);
        
        return $category;
    }

    /**
     * Generate a unique slug for the category.
     */
    private function generateUniqueSlug(string $name, string $companyId, string $type, string $excludeId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        $query = Category::where('slug', $slug)
            ->where('company_id', $companyId)
            ->where('type', $type);
            
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        while ($query->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
            
            $query = Category::where('slug', $slug)
                ->where('company_id', $companyId)
                ->where('type', $type);
                
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
        }

        return $slug;
    }
}
