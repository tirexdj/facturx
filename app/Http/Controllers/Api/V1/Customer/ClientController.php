<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Actions\Api\V1\Customer\CreateClientAction;
use App\Actions\Api\V1\Customer\DeleteClientAction;
use App\Actions\Api\V1\Customer\GetClientAction;
use App\Actions\Api\V1\Customer\GetClientsAction;
use App\Actions\Api\V1\Customer\ImportClientsAction;
use App\Actions\Api\V1\Customer\UpdateClientAction;
use App\Domain\Customer\Models\Client;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\Api\V1\Customer\ImportClientsRequest;
use App\Http\Requests\Api\V1\Customer\StoreClientRequest;
use App\Http\Requests\Api\V1\Customer\UpdateClientRequest;
use App\Http\Resources\Api\V1\Customer\ClientCollection;
use App\Http\Resources\Api\V1\Customer\ClientResource;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ClientController extends BaseApiController
{
    /**
     * Display a listing of the clients.
     */
    public function index(Request $request): ClientCollection
    {
        // Construire la requête de base avec scope company
        $query = Client::query();
        $this->applyCompanyScope($query);
        
        // Configuration de recherche et filtrage
        $config = [
            'searchFields' => ['name', 'legal_name', 'trading_name', 'siren', 'siret', 'vat_number'],
            'exactFilters' => [
                'client_type', 'category_id', 'currency_code', 'language_code'
            ],
            'booleanFilters' => ['has_credit_limit', 'has_overdue_invoices'],
            'allowedSorts' => [
                'name', 'legal_name', 'trading_name', 'client_type', 'created_at', 'updated_at',
                'siren', 'siret', 'vat_number'
            ],
            'defaultSort' => 'name',
            'defaultDirection' => 'asc'
        ];
        
        // Appliquer la recherche, filtres et tri
        $this->buildQuery($query, $request, $config);
        
        // Gérer les filtres avec la syntaxe filter[]
        $filters = $request->get('filter', []);
        foreach ($config['exactFilters'] as $filter) {
            if (isset($filters[$filter])) {
                $this->applyExactSearch($query, $filter, $filters[$filter]);
            }
        }
        
        foreach ($config['booleanFilters'] as $filter) {
            if (isset($filters[$filter])) {
                $this->applyBooleanSearch($query, $filter, $filters[$filter]);
            }
        }
        
        // Gérer les filtres de date
        $this->applyDateRangeFilter(
            $query,
            'created_at',
            $request->get('created_from'),
            $request->get('created_to')
        );
        
        // Gérer les tags (relation many-to-many)
        if ($request->filled('tags') || isset($filters['tags'])) {
            $tags = $filters['tags'] ?? $request->get('tags');
            if ($tags) {
                $tagsArray = is_array($tags) ? $tags : explode(',', $tags);
                $query->whereHas('tags', function ($q) use ($tagsArray) {
                    $q->whereIn('name', $tagsArray);
                });
            }
        }
        
        // Paginer
        $perPage = min($request->get('per_page', 15), 100);
        $clients = $query->paginate($perPage);

        return new ClientCollection($clients);
    }

    /**
     * Store a newly created client.
     */
    public function store(StoreClientRequest $request, CreateClientAction $action): JsonResponse
    {
        try {
            $data = array_merge($request->validated(), [
                'company_id' => $request->user()->company->id,
                'created_by' => $request->user()->id,
            ]);

            $client = $action->execute($data);

            return response()->json([
                'message' => 'Client créé avec succès.',
                'data' => new ClientResource($client)
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la création du client.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Display the specified client.
     */
    public function show(Request $request, string $id, GetClientAction $action): JsonResponse
    {
        $client = $action->execute($id);

        if (!$client) {
            return response()->json([
                'message' => 'Client non trouvé.'
            ], 404);
        }

        // Check if client belongs to user's company
        if ($client->company_id !== $request->user()->company->id) {
            return response()->json([
                'message' => 'Accès non autorisé.'
            ], 403);
        }

        return response()->json([
            'data' => new ClientResource($client)
        ]);
    }

    /**
     * Update the specified client.
     */
    public function update(UpdateClientRequest $request, Client $client, UpdateClientAction $action): JsonResponse
    {
        try {
            $data = array_merge($request->validated(), [
                'updated_by' => $request->user()->id,
            ]);

            $client = $action->execute($client, $data);

            return response()->json([
                'message' => 'Client mis à jour avec succès.',
                'data' => new ClientResource($client)
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour du client.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove the specified client.
     */
    public function destroy(Request $request, Client $client, DeleteClientAction $action): JsonResponse
    {
        // Check authorization
        if ($client->company_id !== $request->user()->company->id) {
            return response()->json([
                'message' => 'Accès non autorisé.'
            ], 403);
        }

        try {
            $action->execute($client);

            return response()->json([
                'message' => 'Client supprimé avec succès.'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la suppression du client.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Import clients from file.
     */
    public function import(ImportClientsRequest $request, ImportClientsAction $action): JsonResponse
    {
        try {
            $file = $request->file('file');
            $mapping = $request->get('mapping');
            $options = array_merge($request->validated(), [
                'company_id' => $request->user()->company->id,
            ]);

            $results = $action->execute($file, $mapping, $options);

            return $this->successResponse($results, 'Import terminé.');

        } catch (Exception $e) {
            return $this->errorResponse(
                'Erreur lors de l\'import.',
                ['error' => $e->getMessage()],
                422
            );
        }
    }

    /**
     * Get client by SIREN.
     */
    public function getBySiren(Request $request, string $siren, GetClientAction $action): JsonResponse
    {
        $client = $action->getBySiren($siren, $request->user()->company->id);

        if (!$client) {
            return response()->json([
                'message' => 'Client non trouvé.'
            ], 404);
        }

        return response()->json([
            'data' => new ClientResource($client)
        ]);
    }

    /**
     * Get client by SIRET.
     */
    public function getBySiret(Request $request, string $siret, GetClientAction $action): JsonResponse
    {
        $client = $action->getBySiret($siret, $request->user()->company->id);

        if (!$client) {
            return response()->json([
                'message' => 'Client non trouvé.'
            ], 404);
        }

        return response()->json([
            'data' => new ClientResource($client)
        ]);
    }

    /**
     * Get client by VAT number.
     */
    public function getByVatNumber(Request $request, string $vatNumber, GetClientAction $action): JsonResponse
    {
        $client = $action->getByVatNumber($vatNumber, $request->user()->company->id);

        if (!$client) {
            return response()->json([
                'message' => 'Client non trouvé.'
            ], 404);
        }

        return response()->json([
            'data' => new ClientResource($client)
        ]);
    }

    /**
     * Restore a soft-deleted client.
     */
    public function restore(Request $request, string $id, DeleteClientAction $action): JsonResponse
    {
        $client = Client::withTrashed()->find($id);

        if (!$client) {
            return response()->json([
                'message' => 'Client non trouvé.'
            ], 404);
        }

        // Check authorization
        if ($client->company_id !== $request->user()->company->id) {
            return response()->json([
                'message' => 'Accès non autorisé.'
            ], 403);
        }

        if (!$client->trashed()) {
            return response()->json([
                'message' => 'Le client n\'est pas supprimé.'
            ], 422);
        }

        try {
            $action->restore($client);

            return response()->json([
                'message' => 'Client restauré avec succès.',
                'data' => new ClientResource($client->fresh())
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la restauration du client.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Force delete a client.
     */
    public function forceDestroy(Request $request, string $id, DeleteClientAction $action): JsonResponse
    {
        $client = Client::withTrashed()->find($id);

        if (!$client) {
            return response()->json([
                'message' => 'Client non trouvé.'
            ], 404);
        }

        // Check authorization (only for company directors)
        if ($client->company_id !== $request->user()->company->id || 
            $request->user()->role?->name !== 'Directeur') {
            return response()->json([
                'message' => 'Accès non autorisé.'
            ], 403);
        }

        try {
            $action->forceDelete($client);

            return response()->json([
                'message' => 'Client supprimé définitivement.'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la suppression définitive.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get client statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $companyId = $this->getUserCompanyId();
        
        $stats = [
            'total' => Client::where('company_id', $companyId)->count(),
            'companies' => Client::where('company_id', $companyId)
                ->where('client_type', 'company')
                ->count(),
            'individuals' => Client::where('company_id', $companyId)
                ->where('client_type', 'individual')
                ->count(),
            'active' => Client::where('company_id', $companyId)
                ->whereNull('deleted_at')
                ->count(),
            'recent' => Client::where('company_id', $companyId)
                ->where('created_at', '>=', now()->subDays(30))
                ->count(),
        ];
        
        return $this->successResponse($stats, 'Statistiques des clients récupérées avec succès.');
    }

    /**
     * Validate SIREN number.
     */
    public function validateSiren(Request $request): JsonResponse
    {
        $request->validate([
            'siren' => 'required|string|size:9|regex:/^[0-9]{9}$/'
        ]);
        
        $siren = $request->get('siren');
        
        // Validation basique du format SIREN
        if (!preg_match('/^[0-9]{9}$/', $siren)) {
            return $this->successResponse([
                'valid' => false,
                'message' => 'Format SIREN invalide. Le SIREN doit contenir exactement 9 chiffres.'
            ]);
        }
        
        // Vérifier l'unicité dans l'entreprise
        $existingClient = Client::where('company_id', $this->getUserCompanyId())
            ->where('siren', $siren)
            ->first();
            
        if ($existingClient) {
            return $this->successResponse([
                'valid' => false,
                'message' => 'Un client avec ce SIREN existe déjà.',
                'existing_client' => new ClientResource($existingClient)
            ]);
        }
        
        return $this->successResponse([
            'valid' => true,
            'message' => 'SIREN valide et disponible.'
        ]);
    }
}
