<?php

namespace App\Http\Resources\Api\V1\Quote;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class QuoteCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = QuoteResource::class;

    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'current_page' => $this->currentPage(),
                'from' => $this->firstItem(),
                'last_page' => $this->lastPage(),
                'path' => $this->path(),
                'per_page' => $this->perPage(),
                'to' => $this->lastItem(),
                'total' => $this->total(),
            ],
            'links' => [
                'first' => $this->url(1),
                'last' => $this->url($this->lastPage()),
                'prev' => $this->previousPageUrl(),
                'next' => $this->nextPageUrl(),
            ],
            'summary' => [
                'total_quotes' => $this->total(),
                'status_counts' => $this->getStatusCounts(),
                'total_amount' => $this->getTotalAmount(),
            ],
        ];
    }

    /**
     * Obtenir le nombre de devis par statut
     */
    private function getStatusCounts(): array
    {
        $counts = $this->collection->groupBy('status')->map->count();
        
        return [
            'draft' => $counts->get('draft', 0),
            'sent' => $counts->get('sent', 0),
            'pending' => $counts->get('pending', 0),
            'accepted' => $counts->get('accepted', 0),
            'declined' => $counts->get('declined', 0),
            'expired' => $counts->get('expired', 0),
            'converted' => $counts->get('converted', 0),
        ];
    }

    /**
     * Obtenir le montant total des devis
     */
    private function getTotalAmount(): float
    {
        return $this->collection->sum('total');
    }

    /**
     * Customize the response data.
     */
    public function with(Request $request): array
    {
        return [
            'filters' => [
                'applied' => $this->getAppliedFilters($request),
                'available' => $this->getAvailableFilters(),
            ],
            'sorting' => [
                'current' => $request->input('sort', 'created_at'),
                'available' => [
                    'quote_number' => 'Numéro de devis',
                    'quote_date' => 'Date du devis',
                    'valid_until' => 'Date de validité',
                    'status' => 'Statut',
                    'total' => 'Montant total',
                    'created_at' => 'Date de création',
                    'updated_at' => 'Dernière modification',
                ],
            ],
        ];
    }

    /**
     * Obtenir les filtres appliqués
     */
    private function getAppliedFilters(Request $request): array
    {
        $filters = [];
        
        if ($request->filled('filter.status')) {
            $filters['status'] = $request->input('filter.status');
        }
        
        if ($request->filled('filter.customer_id')) {
            $filters['customer_id'] = $request->input('filter.customer_id');
        }
        
        if ($request->filled('filter.date_from')) {
            $filters['date_from'] = $request->input('filter.date_from');
        }
        
        if ($request->filled('filter.date_to')) {
            $filters['date_to'] = $request->input('filter.date_to');
        }
        
        if ($request->filled('search')) {
            $filters['search'] = $request->input('search');
        }
        
        return $filters;
    }

    /**
     * Obtenir les filtres disponibles
     */
    private function getAvailableFilters(): array
    {
        return [
            'status' => [
                'draft' => 'Brouillon',
                'sent' => 'Envoyé',
                'pending' => 'En attente',
                'accepted' => 'Accepté',
                'declined' => 'Refusé',
                'expired' => 'Expiré',
                'converted' => 'Converti',
            ],
            'date_range' => [
                'today' => 'Aujourd\'hui',
                'this_week' => 'Cette semaine',
                'this_month' => 'Ce mois',
                'this_quarter' => 'Ce trimestre',
                'this_year' => 'Cette année',
                'custom' => 'Période personnalisée',
            ],
        ];
    }
}
