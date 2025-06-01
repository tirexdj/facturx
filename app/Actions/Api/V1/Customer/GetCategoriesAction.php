<?php

namespace App\Actions\Api\V1\Customer;

use App\Domain\Customer\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class GetCategoriesAction
{
    /**
     * Execute the action to get categories with filters and search.
     */
    public function execute(array $params): Collection
    {
        $query = Category::query();

        // Apply company filter
        if (isset($params['company_id'])) {
            $query->where('company_id', $params['company_id']);
        }

        // Apply type filter
        if (isset($params['type'])) {
            $query->where('type', $params['type']);
        }

        // Apply search
        if (isset($params['search']) && !empty($params['search'])) {
            $search = $params['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('description', 'ILIKE', "%{$search}%");
            });
        }

        // Apply parent filter
        if (isset($params['parent_id'])) {
            if ($params['parent_id'] === 'null' || $params['parent_id'] === null) {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $params['parent_id']);
            }
        }

        // Include hierarchy if requested
        if (isset($params['include_hierarchy']) && $params['include_hierarchy']) {
            $query->with(['parent', 'children']);
        }

        // Apply sorting
        $this->applySorting($query, $params);

        return $query->get();
    }

    /**
     * Get categories in hierarchical structure.
     */
    public function getHierarchical(string $companyId, string $type = null): Collection
    {
        $query = Category::where('company_id', $companyId)
            ->whereNull('parent_id')
            ->with(['children' => function ($query) {
                $query->orderBy('position')->orderBy('name');
            }]);

        if ($type) {
            $query->where('type', $type);
        }

        return $query->orderBy('position')->orderBy('name')->get();
    }

    /**
     * Apply sorting to the query.
     */
    private function applySorting(Builder $query, array $params): void
    {
        $sortBy = $params['sort_by'] ?? 'position';
        $sortOrder = $params['sort_order'] ?? 'asc';

        // Validate sort order
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'asc';
        }

        // Apply sorting based on sort_by parameter
        switch ($sortBy) {
            case 'name':
            case 'type':
            case 'position':
            case 'created_at':
            case 'updated_at':
                $query->orderBy($sortBy, $sortOrder);
                break;
                
            case 'parent':
                $query->leftJoin('categories as parent_categories', 'categories.parent_id', '=', 'parent_categories.id')
                      ->orderBy('parent_categories.name', $sortOrder)
                      ->select('categories.*');
                break;
                
            default:
                $query->orderBy('position', 'asc');
                break;
        }

        // Always add name as secondary sort for consistency
        if ($sortBy !== 'name') {
            $query->orderBy('name', 'asc');
        }
    }
}
