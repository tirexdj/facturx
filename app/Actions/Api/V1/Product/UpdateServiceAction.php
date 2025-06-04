<?php

namespace App\Actions\Api\V1\Product;

use App\Domain\Product\Models\Service;
use Illuminate\Support\Facades\DB;

class UpdateServiceAction
{
    public function execute(Service $service, array $data): Service
    {
        return DB::transaction(function () use ($service, $data) {
            $oldAttributes = $service->toArray();

            $service->update([
                'name' => $data['name'] ?? $service->name,
                'description' => $data['description'] ?? $service->description,
                'category_id' => $data['category_id'] ?? $service->category_id,
                'unit_price' => $data['unit_price'] ?? $service->unit_price,
                'cost_price' => $data['cost_price'] ?? $service->cost_price,
                'vat_rate' => $data['vat_rate'] ?? $service->vat_rate,
                'unit' => $data['unit'] ?? $service->unit,
                'duration' => $data['duration'] ?? $service->duration,
                'is_recurring' => $data['is_recurring'] ?? $service->is_recurring,
                'recurring_period' => $data['recurring_period'] ?? $service->recurring_period,
                'setup_fee' => $data['setup_fee'] ?? $service->setup_fee,
                'is_active' => $data['is_active'] ?? $service->is_active,
                'options' => $data['options'] ?? $service->options,
            ]);

            // Log de l'activité
            activity()
                ->performedOn($service)
                ->withProperties([
                    'old' => $oldAttributes,
                    'attributes' => $service->fresh()->toArray()
                ])
                ->log('Service modifié');

            return $service->fresh();
        });
    }
}
