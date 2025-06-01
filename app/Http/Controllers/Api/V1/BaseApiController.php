<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class BaseApiController extends Controller
{
    /**
     * Return success response.
     */
    protected function successResponse($data = null, string $message = 'Success', int $status = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $status);
    }

    /**
     * Return error response.
     */
    protected function errorResponse(string $message, $errors = null, int $status = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * Return paginated response.
     */
    protected function paginatedResponse($data, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data->items(),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
            ],
            'links' => [
                'first' => $data->url(1),
                'last' => $data->url($data->lastPage()),
                'prev' => $data->previousPageUrl(),
                'next' => $data->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Return created response.
     */
    protected function createdResponse($data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * Return no content response.
     */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Return validation error response.
     */
    protected function validationErrorResponse($errors, string $message = 'The given data was invalid.'): JsonResponse
    {
        return $this->errorResponse($message, $errors, 422);
    }

    /**
     * Return not found response.
     */
    protected function notFoundResponse(string $message = 'Resource not found.'): JsonResponse
    {
        return $this->errorResponse($message, null, 404);
    }

    /**
     * Return unauthorized response.
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized.'): JsonResponse
    {
        return $this->errorResponse($message, null, 401);
    }

    /**
     * Return forbidden response.
     */
    protected function forbiddenResponse(string $message = 'Forbidden.'): JsonResponse
    {
        return $this->errorResponse($message, null, 403);
    }

    /**
     * Get the authenticated user's company.
     */
    protected function getUserCompany()
    {
        return request()->user()->company;
    }

    /**
     * Get the authenticated user's company ID.
     */
    protected function getUserCompanyId(): string
    {
        return request()->user()->company_id;
    }

    /**
     * Get pagination parameters from request.
     */
    protected function getPaginationParams(Request $request): array
    {
        return [
            'page' => $request->get('page', 1),
            'per_page' => min($request->get('per_page', 15), 100), // Max 100 items per page
        ];
    }

    /**
     * Get sorting parameters from request.
     */
    protected function getSortingParams(Request $request): array
    {
        $sort = $request->get('sort', 'created_at');
        $direction = 'asc';

        // Handle descending sort (e.g., "-created_at")
        if (str_starts_with($sort, '-')) {
            $direction = 'desc';
            $sort = substr($sort, 1);
        }

        return [
            'sort' => $sort,
            'direction' => $direction,
        ];
    }

    /**
     * Get search parameters from request.
     */
    protected function getSearchParams(Request $request): array
    {
        return [
            'search' => $request->get('search'),
            'filters' => $request->get('filter', []),
        ];
    }

    /**
     * Apply filters to query.
     */
    protected function applyFilters($query, array $filters)
    {
        foreach ($filters as $field => $value) {
            if ($value !== null && $value !== '') {
                $query->where($field, 'like', "%{$value}%");
            }
        }

        return $query;
    }

    /**
     * Apply search to query.
     */
    protected function applySearch($query, string $search = null, array $searchFields = [])
    {
        if ($search && !empty($searchFields)) {
            $query->where(function ($q) use ($search, $searchFields) {
                $operator = $this->getCaseInsensitiveLikeOperator();
                $searchTerm = $this->formatSearchTerm($search);
                
                foreach ($searchFields as $index => $field) {
                    if ($operator === 'LIKE') {
                        // Pour SQLite, utilise LIKE avec LOWER()
                        if ($index === 0) {
                            $q->whereRaw('LOWER(' . $field . ') LIKE LOWER(?)', [$searchTerm]);
                        } else {
                            $q->orWhereRaw('LOWER(' . $field . ') LIKE LOWER(?)', [$searchTerm]);
                        }
                    } else {
                        // Pour PostgreSQL et autres bases supportant ILIKE
                        if ($index === 0) {
                            $q->where($field, $operator, $searchTerm);
                        } else {
                            $q->orWhere($field, $operator, $searchTerm);
                        }
                    }
                }
            });
        }

        return $query;
    }

    /**
     * Get the appropriate case-insensitive LIKE operator for the current database.
     * 
     * @return string
     */
    protected function getCaseInsensitiveLikeOperator(): string
    {
        return config('database.default') === 'sqlite' ? 'LIKE' : 'ILIKE';
    }

    /**
     * Format search term for database queries.
     * 
     * @param string $search
     * @return string
     */
    protected function formatSearchTerm(string $search): string
    {
        return "%{$search}%";
    }

    /**
     * Apply case-insensitive search to query with database compatibility.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $search
     * @param array $searchFields
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyCaseInsensitiveSearch($query, string $search = null, array $searchFields = [])
    {
        if ($search && !empty($searchFields)) {
            $query->where(function ($q) use ($search, $searchFields) {
                $operator = $this->getCaseInsensitiveLikeOperator();
                $searchTerm = $this->formatSearchTerm($search);
                
                foreach ($searchFields as $index => $field) {
                    if ($operator === 'LIKE') {
                        // Pour SQLite, utilise LIKE avec LOWER()
                        if ($index === 0) {
                            $q->whereRaw('LOWER(' . $field . ') LIKE LOWER(?)', [$searchTerm]);
                        } else {
                            $q->orWhereRaw('LOWER(' . $field . ') LIKE LOWER(?)', [$searchTerm]);
                        }
                    } else {
                        // Pour PostgreSQL et autres bases supportant ILIKE
                        if ($index === 0) {
                            $q->where($field, $operator, $searchTerm);
                        } else {
                            $q->orWhere($field, $operator, $searchTerm);
                        }
                    }
                }
            });
        }

        return $query;
    }

    /**
     * Apply exact search to query for specific field.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $field
     * @param mixed $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyExactSearch($query, string $field, $value)
    {
        if ($value !== null && $value !== '') {
            $query->where($field, $value);
        }

        return $query;
    }

    /**
     * Apply boolean search to query.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $field
     * @param mixed $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyBooleanSearch($query, string $field, $value)
    {
        if ($value !== null && $value !== '') {
            $query->where($field, filter_var($value, FILTER_VALIDATE_BOOLEAN));
        }

        return $query;
    }

    /**
     * Apply sorting to query with validation.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Http\Request $request
     * @param array $allowedSorts
     * @param string $defaultSort
     * @param string $defaultDirection
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applySorting($query, Request $request, array $allowedSorts = [], string $defaultSort = 'created_at', string $defaultDirection = 'desc')
    {
        $sort = $request->get('sort', $defaultSort);
        $direction = $request->get('direction', $defaultDirection);
        
        // Valider la direction
        if (!in_array(strtolower($direction), ['asc', 'desc'])) {
            $direction = $defaultDirection;
        }
        
        // Valider le champ de tri si une liste est fournie
        if (!empty($allowedSorts) && !in_array($sort, $allowedSorts)) {
            $sort = $defaultSort;
        }
        
        return $query->orderBy($sort, $direction);
    }

    /**
     * Build query with common filtering, searching and sorting.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Http\Request $request
     * @param array $config Configuration array with keys: searchFields, exactFilters, booleanFilters, allowedSorts, defaultSort, defaultDirection
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function buildQuery($query, Request $request, array $config = [])
    {
        // Configuration par défaut
        $config = array_merge([
            'searchFields' => [],
            'exactFilters' => [],
            'booleanFilters' => [],
            'allowedSorts' => [],
            'defaultSort' => 'created_at',
            'defaultDirection' => 'desc'
        ], $config);
        
        // Appliquer la recherche textuelle
        if (!empty($config['searchFields'])) {
            $this->applyCaseInsensitiveSearch(
                $query, 
                $request->get('search'), 
                $config['searchFields']
            );
        }
        
        // Appliquer les filtres exacts
        foreach ($config['exactFilters'] as $filter) {
            $this->applyExactSearch($query, $filter, $request->get($filter));
        }
        
        // Appliquer les filtres booléens
        foreach ($config['booleanFilters'] as $filter) {
            $this->applyBooleanSearch($query, $filter, $request->get($filter));
        }
        
        // Appliquer le tri
        $this->applySorting(
            $query, 
            $request, 
            $config['allowedSorts'], 
            $config['defaultSort'], 
            $config['defaultDirection']
        );
        
        return $query;
    }

    /**
     * Apply date range filter to query.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $field
     * @param string|null $startDate
     * @param string|null $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyDateRangeFilter($query, string $field, string $startDate = null, string $endDate = null)
    {
        if ($startDate) {
            $query->where($field, '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where($field, '<=', $endDate);
        }
        
        return $query;
    }

    /**
     * Apply scope filter to current user's company.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $companyField
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyCompanyScope($query, string $companyField = 'company_id')
    {
        return $query->where($companyField, $this->getUserCompanyId());
    }
}
