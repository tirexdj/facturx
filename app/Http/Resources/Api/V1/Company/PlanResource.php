<?php

namespace App\Http\Resources\Api\V1\Company;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'price_monthly' => $this->price_monthly,
            'price_yearly' => $this->price_yearly,
            'currency_code' => $this->currency_code,
            'is_active' => $this->is_active,
            'is_public' => $this->is_public,
            'trial_days' => $this->trial_days,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            
            // Relations
            'features' => $this->whenLoaded('features', function () {
                return $this->features->map(function ($feature) {
                    return [
                        'id' => $feature->id,
                        'name' => $feature->name,
                        'code' => $feature->code,
                        'description' => $feature->description,
                        'is_enabled' => $feature->pivot->is_enabled,
                        'value_limit' => $feature->pivot->value_limit,
                    ];
                });
            }),
            
            'plan_features' => $this->whenLoaded('planFeatures', function () {
                return $this->planFeatures->map(function ($planFeature) {
                    return [
                        'id' => $planFeature->id,
                        'feature_id' => $planFeature->feature_id,
                        'is_enabled' => $planFeature->is_enabled,
                        'value_limit' => $planFeature->value_limit,
                        'feature' => $planFeature->feature ? [
                            'id' => $planFeature->feature->id,
                            'name' => $planFeature->feature->name,
                            'code' => $planFeature->feature->code,
                            'description' => $planFeature->feature->description,
                        ] : null,
                    ];
                });
            }),
            
            // Statistiques
            'stats' => $this->when($request->get('include_stats'), function () {
                return [
                    'companies_count' => $this->companies()->count(),
                    'active_companies_count' => $this->companies()->where('is_active', true)->count(),
                ];
            }),
        ];
    }
}
