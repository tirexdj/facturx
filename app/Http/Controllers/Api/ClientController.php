<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Http\Requests\Client\ImportClientRequest;
use App\Http\Resources\ClientResource;
use App\Http\Resources\ClientCollection;
use App\Models\Client;
use App\Services\ClientService;
use App\Services\ClientImportService;
use App\Services\ClientExportService;
use App\Services\SirenValidationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ClientController extends Controller
{
    public function __construct(
        private ClientService $clientService,
        private ClientImportService $importService,
        private ClientExportService $exportService,
        private SirenValidationService $sirenService
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Liste des clients avec filtres et recherche
     */
    public function index(Request $request): ClientCollection
    {
        Gate::authorize('viewAny', Client::class);

        $filters = $request->validate([
            'search' => 'nullable|string|max:255',
            'type' => 'nullable|in:prospect,client',
            'category_id' => 'nullable|exists:client_categories,id',
            'status' => 'nullable|in:active,inactive',
            'per_page' => 'nullable|integer|min:1|max:100',
            'sort_by' => 'nullable|in:name,email,created_at,updated_at',
            'sort_direction' => 'nullable|in:asc,desc'
        ]);

        $clients = $this->clientService->getFilteredClients(
            $request->user()->current_company_id,
            $filters
        );

        return new ClientCollection($clients);
    }

    /**
     * Création d'un nouveau client
     */
    public function store(StoreClientRequest $request): JsonResponse
    {
        Gate::authorize('create', Client::class);

        // Vérifier la limite pour les comptes gratuits
        $this->clientService->checkClientLimit($request->user()->currentCompany);

        $clientData = $request->validated();
        
        // Valider le SIREN/SIRET si fourni
        if (isset($clientData['siret']) || isset($clientData['siren'])) {
            $validationResult = $this->sirenService->validateSirenSiret(
                $clientData['siren'] ?? null,
                $clientData['siret'] ?? null
            );
            
            if (!$validationResult['valid']) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => [
                        'siren' => [$validationResult['message']]
                    ]
                ], 422);
            }

            // Enrichir les données avec les informations de l'annuaire
            $clientData = array_merge($clientData, $validationResult['data']);
        }

        $client = DB::transaction(function () use ($clientData, $request) {
            return $this->clientService->createClient(
                $request->user()->current_company_id,
                $clientData
            );
        });

        return response()->json([
            'message' => 'Client créé avec succès',
            'data' => new ClientResource($client)
        ], 201);
    }

    /**
     * Affichage d'un client spécifique
     */
    public function show(Client $client): JsonResponse
    {
        Gate::authorize('view', $client);

        $client->load(['category', 'addresses', 'contacts', 'interactions']);

        return response()->json([
            'data' => new ClientResource($client)
        ]);
    }

    /**
     * Mise à jour d'un client
     */
    public function update(UpdateClientRequest $request, Client $client): JsonResponse
    {
        Gate::authorize('update', $client);

        $clientData = $request->validated();
        
        // Valider le SIREN/SIRET si modifié
        if (isset($clientData['siret']) || isset($clientData['siren'])) {
            $validationResult = $this->sirenService->validateSirenSiret(
                $clientData['siren'] ?? $client->siren,
                $clientData['siret'] ?? $client->siret
            );
            
            if (!$validationResult['valid']) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => [
                        'siren' => [$validationResult['message']]
                    ]
                ], 422);
            }

            $clientData = array_merge($clientData, $validationResult['data']);
        }

        $client = DB::transaction(function () use ($client, $clientData) {
            return $this->clientService->updateClient($client, $clientData);
        });

        return response()->json([
            'message' => 'Client mis à jour avec succès',
            'data' => new ClientResource($client)
        ]);
    }

    /**
     * Suppression d'un client
     */
    public function destroy(Client $client): JsonResponse
    {
        Gate::authorize('delete', $client);

        // Vérifier si le client peut être supprimé (pas de factures)
        if ($client->invoices()->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer ce client car il a des factures associées'
            ], 422);
        }

        DB::transaction(function () use ($client) {
            $this->clientService->deleteClient($client);
        });

        return response()->json([
            'message' => 'Client supprimé avec succès'
        ]);
    }

    /**
     * Conversion d'un prospect en client
     */
    public function convertToClient(Client $client): JsonResponse
    {
        Gate::authorize('update', $client);

        if ($client->type !== 'prospect') {
            return response()->json([
                'message' => 'Seuls les prospects peuvent être convertis en clients'
            ], 422);
        }

        $client = DB::transaction(function () use ($client) {
            return $this->clientService->convertProspectToClient($client);
        });

        return response()->json([
            'message' => 'Prospect converti en client avec succès',
            'data' => new ClientResource($client)
        ]);
    }

    /**
     * Import de clients en masse
     */
    public function import(ImportClientRequest $request): JsonResponse
    {
        Gate::authorize('create', Client::class);

        $file = $request->file('file');
        $companyId = $request->user()->current_company_id;

        try {
            $result = DB::transaction(function () use ($file, $companyId) {
                return $this->importService->importClients($file, $companyId);
            });

            return response()->json([
                'message' => 'Import terminé avec succès',
                'data' => [
                    'imported' => $result['imported'],
                    'skipped' => $result['skipped'],
                    'errors' => $result['errors']
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'import',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export des clients
     */
    public function export(Request $request): Response
    {
        Gate::authorize('viewAny', Client::class);

        $format = $request->get('format', 'csv');
        $filters = $request->only(['search', 'type', 'category_id', 'status']);

        $companyId = $request->user()->current_company_id;

        try {
            $exportData = $this->exportService->exportClients($companyId, $filters, $format);

            return response($exportData['content'])
                ->header('Content-Type', $exportData['content_type'])
                ->header('Content-Disposition', 'attachment; filename="' . $exportData['filename'] . '"');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'export',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validation d'un SIREN/SIRET
     */
    public function validateSiren(Request $request): JsonResponse
    {
        $request->validate([
            'siren' => 'nullable|string|size:9',
            'siret' => 'nullable|string|size:14'
        ]);

        $result = $this->sirenService->validateSirenSiret(
            $request->get('siren'),
            $request->get('siret')
        );

        return response()->json($result);
    }

    /**
     * Statistiques des clients
     */
    public function statistics(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Client::class);

        $companyId = $request->user()->current_company_id;
        $stats = $this->clientService->getClientStatistics($companyId);

        return response()->json([
            'data' => $stats
        ]);
    }
}
