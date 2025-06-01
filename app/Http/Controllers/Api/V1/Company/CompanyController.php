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
        $query = Company::query()->with(['plan', 'addresses', 'phoneNumbers', 'emails']);
        
        // Configuration pour la construction de la requête
        $config = [
            'searchFields' => ['name', 'legal_name', 'siren', 'siret'],
            'exactFilters' => ['plan_id'],
            'booleanFilters' => ['is_active'],
            'allowedSorts' => ['name', 'legal_name', 'siren', 'siret', 'created_at', 'updated_at'],
            'defaultSort' => 'created_at',
            'defaultDirection' => 'desc'
        ];
        
        // Construire la requête avec les filtres, recherche et tri
        $this->buildQuery($query, $request, $config);
        
        // Paginer les résultats
        $companies = $query->paginate($request->get('per_page', 15));

        return $this->successResponse(
            new CompanyCollection($companies),
            'Companies retrieved successfully'
        );
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
