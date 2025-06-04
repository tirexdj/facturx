<?php

namespace App\Http\Controllers\Api\V1\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Product\StoreCategoryRequest;
use App\Http\Requests\Api\V1\Product\UpdateCategoryRequest;
use App\Http\Resources\Api\V1\Product\CategoryResource;
use App\Http\Resources\Api\V1\Product\CategoryCollection;
use App\Actions\Api\V1\Product\CreateCategoryAction;
use App\Actions\Api\V1\Product\UpdateCategoryAction;
use App\Actions\Api\V1\Product\DeleteCategoryAction;
use App\Actions\Api\V1\Product\GetCategoryAction;
use App\Domain\Product\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company.access', 'plan.limits']);
        $this->authorizeResource(Category::class, 'category');
    }

    /**
     * Display a listing of categories.
     */
    public function index(Request $request): CategoryCollection
    {
        $query = Category::query()
            ->where('company_id', $request->user()->company_id)
            ->with(['parent', 'children'])
            ->withCount(['products', 'services']);

        // Filtrage
        if ($request->has('filter')) {
            $filters = $request->get('filter');
            
            if (isset($filters['name'])) {
                $query->where('name', 'like', '%' . $filters['name'] . '%');
            }
            
            if (isset($filters['parent_id'])) {
                if ($filters['parent_id'] === 'null') {
                    $query->whereNull('parent_id');
                } else {
                    $query->where('parent_id', $filters['parent_id']);
                }
            }
            
            if (isset($filters['type'])) {
                $query->where('type', $filters['type']);
            }
        }

        // Recherche
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        // Tri
        if ($request->has('sort')) {
            $sort = $request->get('sort');
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $field = ltrim($sort, '-');
            $query->orderBy($field, $direction);
        } else {
            $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
        }

        // Relations à inclure
        if ($request->has('include')) {
            $includes = explode(',', $request->get('include'));
            $availableIncludes = ['parent', 'children', 'products', 'services'];
            $validIncludes = array_intersect($includes, $availableIncludes);
            
            if (!empty($validIncludes)) {
                $query->with($validIncludes);
            }
        }

        $perPage = min($request->get('per_page', 15), 100);
        $categories = $query->paginate($perPage);

        return new CategoryCollection($categories);
    }

    /**
     * Store a newly created category.
     */
    public function store(StoreCategoryRequest $request, CreateCategoryAction $action): CategoryResource
    {
        $category = $action->execute($request->validated());

        return new CategoryResource($category->load(['parent', 'children']));
    }

    /**
     * Display the specified category.
     */
    public function show(Category $category, GetCategoryAction $action): CategoryResource
    {
        $categoryData = $action->execute($category);

        return new CategoryResource($categoryData);
    }

    /**
     * Update the specified category.
     */
    public function update(UpdateCategoryRequest $request, Category $category, UpdateCategoryAction $action): CategoryResource
    {
        $category = $action->execute($category, $request->validated());

        return new CategoryResource($category->load(['parent', 'children']));
    }

    /**
     * Remove the specified category.
     */
    public function destroy(Category $category, DeleteCategoryAction $action): Response
    {
        $action->execute($category);

        return response()->noContent();
    }
}
