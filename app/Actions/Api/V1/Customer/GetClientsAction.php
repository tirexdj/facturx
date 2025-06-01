<?php

namespace App\Actions\Api\V1\Customer;

use App\Domain\Customer\Models\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class GetClientsAction
{
    /**
     * Execute the action to get clients with filters, search and pagination.
     */
    public function execute(array $params): LengthAwarePaginator
    {
        $query = Client::query()
            ->with(['category', 'paymentTerms']);

        // Apply company filter
        if (isset($params['company_id'])) {
            $query->where('company_id', $params['company_id']);
        }

        // Apply search
        if (isset($params['search']) && !empty($params['search'])) {
            $search = $params['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('legal_name', 'ILIKE', "%{$search}%")
                  ->orWhere('trading_name', 'ILIKE', "%{$search}%")
                  ->orWhere('siren', 'ILIKE', "%{$search}%")
                  ->orWhere('siret', 'ILIKE', "%{$search}%")
                  ->orWhere('vat_number', 'ILIKE', "%{$search}%")
                  ->orWhere('email', 'ILIKE', "%{$search}%");
            });
        }

        // Apply filters
        $this->applyFilters($query, $params);

        // Apply sorting
        $this->applySorting($query, $params);

        // Apply pagination
        $perPage = min($params['per_page'] ?? 15, 100);
        
        return $query->paginate($perPage);
    }

    /**
     * Apply filters to the query.
     */
    private function applyFilters(Builder $query, array $params): void
    {
        // Filter by client type
        if (isset($params['client_type'])) {
            $query->where('client_type', $params['client_type']);
        }

        // Filter by category
        if (isset($params['category_id'])) {
            $query->where('category_id', $params['category_id']);
        }

        // Filter by tags
        if (isset($params['tags']) && is_array($params['tags'])) {
            $query->where(function (Builder $q) use ($params) {
                foreach ($params['tags'] as $tag) {
                    $q->orWhereJsonContains('tags', $tag);
                }
            });
        }

        // Filter by currency
        if (isset($params['currency_code'])) {
            $query->where('currency_code', $params['currency_code']);
        }

        // Filter by language
        if (isset($params['language_code'])) {
            $query->where('language_code', $params['language_code']);
        }

        // Filter by credit limit
        if (isset($params['has_credit_limit'])) {
            if ($params['has_credit_limit']) {
                $query->whereNotNull('credit_limit')->where('credit_limit', '>', 0);
            } else {
                $query->whereNull('credit_limit')->orWhere('credit_limit', '<=', 0);
            }
        }

        // Filter by overdue invoices
        if (isset($params['has_overdue_invoices']) && $params['has_overdue_invoices']) {
            $query->whereHas('invoices', function (Builder $q) {
                $q->whereIn('status', ['sent', 'partial'])
                  ->where('due_date', '<', now());
            });
        }

        // Filter by creation date range
        if (isset($params['created_from'])) {
            $query->where('created_at', '>=', $params['created_from']);
        }
        if (isset($params['created_to'])) {
            $query->where('created_at', '<=', $params['created_to']);
        }

        // Filter by country (from addresses)
        if (isset($params['country_code'])) {
            $query->whereHas('addresses', function (Builder $q) use ($params) {
                $q->where('country_code', $params['country_code']);
            });
        }

        // Filter by city (from addresses)
        if (isset($params['city'])) {
            $query->whereHas('addresses', function (Builder $q) use ($params) {
                $q->where('city', 'ILIKE', "%{$params['city']}%");
            });
        }
    }

    /**
     * Apply sorting to the query.
     */
    private function applySorting(Builder $query, array $params): void
    {
        $sortBy = $params['sort_by'] ?? 'name';
        $sortOrder = $params['sort_order'] ?? 'asc';

        // Validate sort order
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'asc';
        }

        // Apply sorting based on sort_by parameter
        switch ($sortBy) {
            case 'name':
            case 'legal_name':
            case 'trading_name':
            case 'client_type':
            case 'currency_code':
            case 'language_code':
            case 'created_at':
            case 'updated_at':
                $query->orderBy($sortBy, $sortOrder);
                break;
                
            case 'category':
                $query->leftJoin('categories', 'clients.category_id', '=', 'categories.id')
                      ->orderBy('categories.name', $sortOrder)
                      ->select('clients.*');
                break;
                
            case 'payment_terms':
                $query->leftJoin('payment_terms', 'clients.payment_terms_id', '=', 'payment_terms.id')
                      ->orderBy('payment_terms.name', $sortOrder)
                      ->select('clients.*');
                break;
                
            default:
                $query->orderBy('name', 'asc');
                break;
        }
    }
}
