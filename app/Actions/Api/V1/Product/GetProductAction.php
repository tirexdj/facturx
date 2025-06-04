<?php

namespace App\Actions\Api\V1\Product;

use App\Domain\Product\Models\Product;

class GetProductAction
{
    public function execute(Product $product): Product
    {
        return $product->load(['category']);
    }
}
