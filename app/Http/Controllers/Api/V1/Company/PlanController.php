<?php

namespace App\Http\Controllers\Api\V1\Company;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Api\V1\Company\PlanResource;
use App\Http\Resources\Api\V1\Company\PlanCollection;
use App\Domain\Company\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanController extends BaseApiController
{
    /**
     * Display a listing of available plans.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $plans = Plan::query()
            ->with(['features'])
            ->when($request->has('is_public'), function ($query) use ($request) {
                $query->where('is_public', filter_var($request->get('is_public'), FILTER_VALIDATE_BOOLEAN));
            }, function ($query) {
                // Par défaut, afficher seulement les plans publics
                $query->where('is_public', true);
            })
            ->when($request->has('is_active'), function ($query) use ($request) {
                $query->where('is_active', filter_var($request->get('is_active'), FILTER_VALIDATE_BOOLEAN));
            }, function ($query) {
                // Par défaut, afficher seulement les plans actifs
                $query->where('is_active', true);
            })
            ->orderBy('price_monthly', 'asc')
            ->get();

        return $this->successResponse(
            new PlanCollection($plans),
            'Plans retrieved successfully'
        );
    }

    /**
     * Display the specified plan.
     * 
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $plan = Plan::with(['features', 'planFeatures'])
            ->where('is_public', true)
            ->where('is_active', true)
            ->findOrFail($id);

        return $this->successResponse(
            new PlanResource($plan),
            'Plan retrieved successfully'
        );
    }
}
