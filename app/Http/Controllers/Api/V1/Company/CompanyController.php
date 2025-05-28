<?php

namespace App\Http\Controllers\Api\V1\Company;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\Company\StoreCompanyRequest;
use App\Http\Requests\Api\V1\Company\UpdateCompanyRequest;
use App\Http\Resources\Api\V1\Company\CompanyResource;
use App\Http\Resources\Api\V1\Company\CompanyCollection;
use App\Actions\Api\V1\Company\CreateCompanyAction;
use App\Actions\Api\V1\Company\UpdateCompanyAction;
use App\Actions\Api\V1\Company\DeleteCompanyAction;
use App\Actions\Api\V1\Company\GetCompanyAction;
use App\Domain\Company\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends BaseApiController
{
    /**
     * Display a listing of companies.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $companies = Company::query()
            ->with(['plan', 'addresses', 'phoneNumbers', 'emails'])
            ->when($request->get('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    // Utilise une approche compatible avec SQLite et PostgreSQL
                    $operator = $this->getCaseInsensitiveLikeOperator();
                    $searchTerm = $this->formatSearchTerm($search);
                    
                    if ($operator === 'LIKE') {
                        // Pour SQLite, utilise LIKE avec LOWER()
                        $q->whereRaw('LOWER(name) LIKE LOWER(?)', [$searchTerm])
                          ->orWhereRaw('LOWER(legal_name) LIKE LOWER(?)', [$searchTerm])
                          ->orWhereRaw('LOWER(siren) LIKE LOWER(?)', [$searchTerm])
                          ->orWhereRaw('LOWER(siret) LIKE LOWER(?)', [$searchTerm]);
                    } else {
                        // Pour PostgreSQL et autres bases supportant ILIKE
                        $q->where('name', $operator, $searchTerm)
                          ->orWhere('legal_name', $operator, $searchTerm)
                          ->orWhere('siren', $operator, $searchTerm)
                          ->orWhere('siret', $operator, $searchTerm);
                    }
                });
            })
            ->when($request->get('plan_id'), function ($query, $planId) {
                $query->where('plan_id', $planId);
            })
            ->when($request->get('is_active'), function ($query, $isActive) {
                $query->where('is_active', filter_var($isActive, FILTER_VALIDATE_BOOLEAN));
            })
            ->orderBy(
                $request->get('sort', 'created_at'),
                $request->get('direction', 'desc')
            )
            ->paginate($request->get('per_page', 15));

        return $this->successResponse(
            new CompanyCollection($companies),
            'Companies retrieved successfully'
        );
    }

    /**
     * Get the appropriate case-insensitive LIKE operator for the current database.
     * 
     * @return string
     */
    private function getCaseInsensitiveLikeOperator(): string
    {
        return config('database.default') === 'sqlite' ? 'LIKE' : 'ILIKE';
    }

    /**
     * Format search term for the current database.
     * 
     * @param string $search
     * @return string
     */
    private function formatSearchTerm(string $search): string
    {
        return "%{$search}%";
    }

    /**
     * Store a newly created company.
     * 
     * @param StoreCompanyRequest $request
     * @param CreateCompanyAction $action
     * @return JsonResponse
     */
    public function store(StoreCompanyRequest $request, CreateCompanyAction $action): JsonResponse
    {
        $company = $action->execute($request->validated());

        return $this->successResponse(
            new CompanyResource($company->load(['plan', 'addresses', 'phoneNumbers', 'emails'])),
            'Company created successfully',
            201
        );
    }

    /**
     * Display the specified company.
     * 
     * @param string $id
     * @param GetCompanyAction $action
     * @return JsonResponse
     */
    public function show(string $id, GetCompanyAction $action): JsonResponse
    {
        $company = $action->execute($id);

        return $this->successResponse(
            new CompanyResource($company->load(['plan', 'addresses', 'phoneNumbers', 'emails', 'users'])),
            'Company retrieved successfully'
        );
    }

    /**
     * Update the specified company.
     * 
     * @param UpdateCompanyRequest $request
     * @param string $id
     * @param UpdateCompanyAction $action
     * @return JsonResponse
     */
    public function update(UpdateCompanyRequest $request, string $id, UpdateCompanyAction $action): JsonResponse
    {
        $company = $action->execute($id, $request->validated());

        return $this->successResponse(
            new CompanyResource($company->load(['plan', 'addresses', 'phoneNumbers', 'emails'])),
            'Company updated successfully'
        );
    }

    /**
     * Remove the specified company.
     * 
     * @param string $id
     * @param DeleteCompanyAction $action
     * @return JsonResponse
     */
    public function destroy(string $id, DeleteCompanyAction $action): JsonResponse
    {
        $action->execute($id);

        return $this->successResponse(
            null,
            'Company deleted successfully'
        );
    }

    /**
     * Display the authenticated user's company.
     * 
     * @param Request $request
     * @param GetCompanyAction $action
     * @return JsonResponse
     */
    public function showOwnCompany(Request $request, GetCompanyAction $action): JsonResponse
    {
        $user = $request->user();
        $company = $action->execute($user->company_id);

        return $this->successResponse(
            new CompanyResource($company->load(['plan', 'addresses', 'phoneNumbers', 'emails', 'users'])),
            'Company retrieved successfully'
        );
    }

    /**
     * Update the authenticated user's company.
     * 
     * @param UpdateCompanyRequest $request
     * @param UpdateCompanyAction $action
     * @return JsonResponse
     */
    public function updateOwnCompany(UpdateCompanyRequest $request, UpdateCompanyAction $action): JsonResponse
    {
        $user = $request->user();
        $company = $action->execute($user->company_id, $request->validated());

        return $this->successResponse(
            new CompanyResource($company->load(['plan', 'addresses', 'phoneNumbers', 'emails'])),
            'Company updated successfully'
        );
    }
}
