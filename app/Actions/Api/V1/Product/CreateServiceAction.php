<?php

namespace App\Actions\Api\V1\Product;

use App\Domain\Product\Models\Service;
use Illuminate\Support\Facades\DB;

class CreateServiceAction
{
    public function execute(array $data): Service
    {
        return DB::transaction(function () use ($data) {
            $service = Service::create([
                'company_id' => $data['company_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'unit_price' => $data['unit_price'],
                'cost_price' => $data['cost_price'] ?? null,
                'vat_rate' => $data['vat_rate'],
                'unit' => $data['unit'],
                'duration' => $data['duration'] ?? null,
                'is_recurring' => $data['is_recurring'] ?? false,
                'recurring_period' => $data['recurring_period'] ?? null,
                'setup_fee' => $data['setup_fee'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'options' => $data['options'] ?? null,
            ]);

            // Log de l'activité
            activity()
                ->performedOn($service)
                ->withProperties(['attributes' => $data])
                ->log('Service créé');

            return $service;
        });
    }
}
