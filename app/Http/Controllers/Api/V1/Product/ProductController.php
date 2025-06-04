<?php

namespace App\Http\Controllers\Api\V1\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Product\StoreProductRequest;
use App\Http\Requests\Api\V1\Product\UpdateProductRequest;
use App\Http\Resources\Api\V1\Product\ProductResource;
use App\Http\Resources\Api\V1\Product\ProductCollection;
use App\Actions\Api\V1\Product\CreateProductAction;
use App\Actions\Api\V1\Product\UpdateProductAction;
use App\Actions\Api\V1\Product\DeleteProductAction;
use App\Actions\Api\V1\Product\GetProductAction;
use App\Domain\Product\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company.access', 'plan.limits']);
        $this->authorizeResource(Product::class, 'product');
    }

    /**
     * Display a listing of products.
     */
    public function index(Request $request): ProductCollection
    {
        $query = Product::query()
            ->where('company_id', $request->user()->company_id)
            ->with(['category']);

        // Filtrage
        if ($request->has('filter')) {
            $filters = $request->get('filter');
            
            if (isset($filters['name'])) {
                $query->where('name', 'like', '%' . $filters['name'] . '%');
            }
            
            if (isset($filters['category_id'])) {
                $query->where('category_id', $filters['category_id']);
            }
            
            if (isset($filters['active'])) {
                $query->where('is_active', $filters['active']);
            }
        }

        // Recherche
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%')
                  ->orWhere('reference', 'like', '%' . $search . '%');
            });
        }

        // Tri
        if ($request->has('sort')) {
            $sort = $request->get('sort');
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $field = ltrim($sort, '-');
            $query->orderBy($field, $direction);
        } else {
            $query->orderBy('name', 'asc');
        }

        // Relations à inclure
        if ($request->has('include')) {
            $includes = explode(',', $request->get('include'));
            $availableIncludes = ['category'];
            $validIncludes = array_intersect($includes, $availableIncludes);
            
            if (!empty($validIncludes)) {
                $query->with($validIncludes);
            }
        }

        $perPage = min($request->get('per_page', 15), 100);
        $products = $query->paginate($perPage);

        return new ProductCollection($products);
    }

    /**
     * Store a newly created product.
     */
    public function store(StoreProductRequest $request, CreateProductAction $action): ProductResource
    {
        $product = $action->execute($request->validated());

        return new ProductResource($product->load(['category']));
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product, GetProductAction $action): ProductResource
    {
        $productData = $action->execute($product);

        return new ProductResource($productData);
    }

    /**
     * Update the specified product.
     */
    public function update(UpdateProductRequest $request, Product $product, UpdateProductAction $action): ProductResource
    {
        $product = $action->execute($product, $request->validated());

        return new ProductResource($product->load(['category']));
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Product $product, DeleteProductAction $action): Response
    {
        $action->execute($product);

        return response()->noContent();
    }
}
