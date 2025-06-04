<?php

namespace App\Actions\Api\V1\Product;

use App\Domain\Product\Models\Service;

class GetServiceAction
{
    public function execute(Service $service): Service
    {
        return $service->load(['category']);
    }
}
