<?php

namespace App\Http\Controllers\Api\V1\Quote;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Quote\StoreQuoteRequest;
use App\Http\Requests\Api\V1\Quote\UpdateQuoteRequest;
use App\Http\Requests\Api\V1\Quote\SendQuoteRequest;
use App\Http\Resources\Api\V1\Quote\QuoteResource;
use App\Http\Resources\Api\V1\Quote\QuoteCollection;
use App\Actions\Api\V1\Quote\CreateQuoteAction;
use App\Actions\Api\V1\Quote\UpdateQuoteAction;
use App\Actions\Api\V1\Quote\DeleteQuoteAction;
use App\Actions\Api\V1\Quote\SendQuoteAction;
use App\Actions\Api\V1\Quote\ConvertQuoteToInvoiceAction;
use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class QuoteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('company.access');
    }

    /**
     * Liste paginée des devis de l'entreprise connectée
     */
    public function index(Request $request): QuoteCollection
    {
        $query = Quote::where('company_id', Auth::user()->company_id)
            ->with(['customer', 'items.product', 'statusHistories'])
            ->latest();

        // Filtres
        if ($request->filled('filter.status')) {
            $query->where('status', $request->input('filter.status'));
        }

        if ($request->filled('filter.customer_id')) {
            $query->where('customer_id', $request->input('filter.customer_id'));
        }

        if ($request->filled('filter.date_from')) {
            $query->whereDate('quote_date', '>=', $request->input('filter.date_from'));
        }

        if ($request->filled('filter.date_to')) {
            $query->whereDate('quote_date', '<=', $request->input('filter.date_to'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('quote_number', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($customerQuery) use ($search) {
                      $customerQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Tri
        if ($request->filled('sort')) {
            $sortField = ltrim($request->input('sort'), '-');
            $sortDirection = str_starts_with($request->input('sort'), '-') ? 'desc' : 'asc';
            $query->orderBy($sortField, $sortDirection);
        }

        // Relations à inclure
        if ($request->filled('include')) {
            $includes = explode(',', $request->input('include'));
            foreach ($includes as $include) {
                if (in_array($include, ['customer', 'items', 'statusHistories'])) {
                    $query->with($include);
                }
            }
        }

        $quotes = $query->paginate($request->input('per_page', 15));

        return new QuoteCollection($quotes);
    }

    /**
     * Créer un nouveau devis
     */
    public function store(StoreQuoteRequest $request, CreateQuoteAction $action): JsonResponse
    {
        try {
            $quote = $action->execute($request->validated());

            return response()->json([
                'message' => 'Devis créé avec succès',
                'data' => new QuoteResource($quote->load(['customer', 'items.product', 'statusHistories']))
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la création du devis',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue'
            ], 500);
        }
    }

    /**
     * Afficher un devis spécifique
     */
    public function show(Request $request, Quote $quote): JsonResponse
    {
        Gate::authorize('view', $quote);

        // Relations à inclure
        $with = ['customer', 'items.product', 'statusHistories.user', 'company'];
        if ($request->filled('include')) {
            $includes = explode(',', $request->input('include'));
            $with = array_intersect($includes, $with);
        }

        return response()->json([
            'data' => new QuoteResource($quote->load($with))
        ]);
    }

    /**
     * Mettre à jour un devis
     */
    public function update(UpdateQuoteRequest $request, Quote $quote, UpdateQuoteAction $action): JsonResponse
    {
        Gate::authorize('update', $quote);

        try {
            $updatedQuote = $action->execute($quote, $request->validated());

            return response()->json([
                'message' => 'Devis mis à jour avec succès',
                'data' => new QuoteResource($updatedQuote->load(['customer', 'items.product', 'statusHistories']))
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour du devis',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue'
            ], 500);
        }
    }

    /**
     * Supprimer un devis
     */
    public function destroy(Quote $quote, DeleteQuoteAction $action): JsonResponse
    {
        Gate::authorize('delete', $quote);

        try {
            $action->execute($quote);

            return response()->json([
                'message' => 'Devis supprimé avec succès'
            ], 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la suppression du devis',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue'
            ], 500);
        }
    }

    /**
     * Envoyer un devis par email
     */
    public function send(SendQuoteRequest $request, Quote $quote, SendQuoteAction $action): JsonResponse
    {
        Gate::authorize('update', $quote);

        try {
            $result = $action->execute($quote, $request->validated());

            return response()->json([
                'message' => 'Devis envoyé avec succès',
                'data' => [
                    'sent_at' => $result['sent_at'],
                    'recipient' => $result['recipient']
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'envoi du devis',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue'
            ], 500);
        }
    }

    /**
     * Convertir un devis en facture
     */
    public function convertToInvoice(Quote $quote, ConvertQuoteToInvoiceAction $action): JsonResponse
    {
        Gate::authorize('update', $quote);

        try {
            $invoice = $action->execute($quote);

            return response()->json([
                'message' => 'Devis converti en facture avec succès',
                'data' => [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la conversion du devis',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue'
            ], 500);
        }
    }

    /**
     * Télécharger le PDF d'un devis
     */
    public function downloadPdf(Quote $quote): Response
    {
        Gate::authorize('view', $quote);

        try {
            $pdfService = app('App\Services\PdfGeneratorService');
            $pdf = $pdfService->generateQuotePdf($quote);

            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="devis-' . $quote->quote_number . '.pdf"'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la génération du PDF',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue'
            ], 500);
        }
    }

    /**
     * Dupliquer un devis
     */
    public function duplicate(Quote $quote): JsonResponse
    {
        Gate::authorize('view', $quote);

        try {
            $duplicatedQuote = $quote->replicate();
            $duplicatedQuote->quote_number = null; // Sera généré automatiquement
            $duplicatedQuote->status = 'draft';
            $duplicatedQuote->valid_until = now()->addDays(30);
            $duplicatedQuote->save();

            // Dupliquer les lignes
            foreach ($quote->items as $item) {
                $duplicatedItem = $item->replicate();
                $duplicatedItem->quote_id = $duplicatedQuote->id;
                $duplicatedItem->save();
            }

            return response()->json([
                'message' => 'Devis dupliqué avec succès',
                'data' => new QuoteResource($duplicatedQuote->load(['customer', 'items.product']))
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la duplication du devis',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue'
            ], 500);
        }
    }
}
