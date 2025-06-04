<?php

namespace App\Actions\Api\V1\Product;

use App\Domain\Product\Models\Category;

class GetCategoryAction
{
    public function execute(Category $category): Category
    {
        return $category->load(['parent', 'children', 'products', 'services']);
    }
}
