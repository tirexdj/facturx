<?php

namespace App\Actions\Api\V1\Customer;

use App\Domain\Customer\Models\Category;
use Illuminate\Support\Str;

class CreateCategoryAction
{
    /**
     * Execute the action to create a new category.
     */
    public function execute(array $data): Category
    {
        // Generate slug if not provided
        if (!isset($data['slug']) || empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['name'], $data['company_id'], $data['type']);
        }

        return Category::create($data);
    }

    /**
     * Generate a unique slug for the category.
     */
    private function generateUniqueSlug(string $name, string $companyId, string $type): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (Category::where('slug', $slug)
            ->where('company_id', $companyId)
            ->where('type', $type)
            ->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
