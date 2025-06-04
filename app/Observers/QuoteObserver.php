<?php

namespace App\Observers;

use App\Domain\Quote\Models\Quote;
use App\Enums\QuoteStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class QuoteObserver
{
    /**
     * Handle the Quote "creating" event.
     */
    public function creating(Quote $quote): void
    {
        // Générer automatiquement le numéro de devis si pas fourni
        if (empty($quote->quote_number)) {
            $quote->quote_number = $this->generateQuoteNumber($quote);
        }

        // Définir le statut par défaut
        if (empty($quote->status)) {
            $quote->status = QuoteStatus::DRAFT->value;
        }

        // Définir la date de validité par défaut (30 jours)
        if (empty($quote->valid_until) && !empty($quote->quote_date)) {
            $quote->valid_until = $quote->quote_date->addDays(30);
        }
    }

    /**
     * Handle the Quote "created" event.
     */
    public function created(Quote $quote): void
    {
        // Créer un historique de statut initial
        $quote->statusHistories()->create([
            'status' => $quote->status,
            'comment' => 'Devis créé',
            'user_id' => Auth::id(),
            'created_at' => now()
        ]);

        Log::info('Quote created', [
            'quote_id' => $quote->id,
            'quote_number' => $quote->quote_number,
            'company_id' => $quote->company_id,
            'customer_id' => $quote->customer_id,
            'total' => $quote->total
        ]);
    }

    /**
     * Handle the Quote "updating" event.
     */
    public function updating(Quote $quote): void
    {
        // Vérifier si le statut change
        if ($quote->isDirty('status')) {
            $oldStatus = $quote->getOriginal('status');
            $newStatus = $quote->status;
            
            Log::info('Quote status changing', [
                'quote_id' => $quote->id,
                'quote_number' => $quote->quote_number,
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ]);

            // Valider la transition de statut
            $this->validateStatusTransition($quote, $oldStatus, $newStatus);
        }

        // Mettre à jour automatiquement sent_at quand le statut passe à SENT
        if ($quote->isDirty('status') && $quote->status === QuoteStatus::SENT->value && empty($quote->sent_at)) {
            $quote->sent_at = now();
        }
    }

    /**
     * Handle the Quote "updated" event.
     */
    public function updated(Quote $quote): void
    {
        // Créer un historique si le statut a changé
        if ($quote->wasChanged('status')) {
            $quote->statusHistories()->create([
                'status' => $quote->status,
                'comment' => $this->getStatusChangeComment($quote->getOriginal('status'), $quote->status),
                'user_id' => Auth::id(),
                'created_at' => now()
            ]);
        }

        // Vérifier si le devis est expiré
        $this->checkExpiration($quote);

        Log::info('Quote updated', [
            'quote_id' => $quote->id,
            'quote_number' => $quote->quote_number,
            'changes' => $quote->getChanges()
        ]);
    }

    /**
     * Handle the Quote "deleted" event.
     */
    public function deleted(Quote $quote): void
    {
        Log::info('Quote deleted', [
            'quote_id' => $quote->id,
            'quote_number' => $quote->quote_number,
            'company_id' => $quote->company_id
        ]);
    }

    /**
     * Handle the Quote "restored" event.
     */
    public function restored(Quote $quote): void
    {
        Log::info('Quote restored', [
            'quote_id' => $quote->id,
            'quote_number' => $quote->quote_number
        ]);
    }

    /**
     * Handle the Quote "force deleted" event.
     */
    public function forceDeleted(Quote $quote): void
    {
        Log::warning('Quote force deleted', [
            'quote_id' => $quote->id,
            'quote_number' => $quote->quote_number
        ]);
    }

    /**
     * Générer un numéro de devis unique
     */
    private function generateQuoteNumber(Quote $quote): string
    {
        $company = $quote->company;
        $prefix = $company->quote_prefix ?? 'DEV';
        $year = now()->format('Y');
        
        // Trouver le dernier numéro pour cette entreprise cette année
        $lastQuote = Quote::where('company_id', $company->id)
            ->whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastQuote && preg_match('/(\d+)$/', $lastQuote->quote_number, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $year, $nextNumber);
    }

    /**
     * Valider la transition de statut
     */
    private function validateStatusTransition(Quote $quote, ?string $oldStatus, string $newStatus): void
    {
        $validTransitions = [
            QuoteStatus::DRAFT->value => [
                QuoteStatus::SENT->value,
                QuoteStatus::PENDING->value,
                QuoteStatus::ACCEPTED->value,
                QuoteStatus::DECLINED->value
            ],
            QuoteStatus::SENT->value => [
                QuoteStatus::PENDING->value,
                QuoteStatus::ACCEPTED->value,
                QuoteStatus::DECLINED->value,
                QuoteStatus::EXPIRED->value
            ],
            QuoteStatus::PENDING->value => [
                QuoteStatus::ACCEPTED->value,
                QuoteStatus::DECLINED->value,
                QuoteStatus::EXPIRED->value
            ],
            QuoteStatus::ACCEPTED->value => [
                QuoteStatus::CONVERTED->value
            ],
            QuoteStatus::DECLINED->value => [],
            QuoteStatus::EXPIRED->value => [],
            QuoteStatus::CONVERTED->value => []
        ];

        if ($oldStatus && isset($validTransitions[$oldStatus])) {
            if (!in_array($newStatus, $validTransitions[$oldStatus])) {
                throw new \InvalidArgumentException(
                    "Transition de statut invalide : de '{$oldStatus}' vers '{$newStatus}'"
                );
            }
        }
    }

    /**
     * Obtenir le commentaire pour un changement de statut
     */
    private function getStatusChangeComment(string $oldStatus, string $newStatus): string
    {
        return match($newStatus) {
            QuoteStatus::DRAFT->value => 'Devis remis en brouillon',
            QuoteStatus::SENT->value => 'Devis envoyé au client',
            QuoteStatus::PENDING->value => 'Devis en attente de réponse',
            QuoteStatus::ACCEPTED->value => 'Devis accepté par le client',
            QuoteStatus::DECLINED->value => 'Devis refusé par le client',
            QuoteStatus::EXPIRED->value => 'Devis expiré',
            QuoteStatus::CONVERTED->value => 'Devis converti en facture',
            default => "Statut changé de '{$oldStatus}' vers '{$newStatus}'"
        };
    }

    /**
     * Vérifier si le devis est expiré et mettre à jour le statut
     */
    private function checkExpiration(Quote $quote): void
    {
        if ($quote->valid_until && 
            $quote->valid_until->isPast() && 
            !in_array($quote->status, [QuoteStatus::ACCEPTED->value, QuoteStatus::CONVERTED->value, QuoteStatus::EXPIRED->value])) {
            
            $quote->updateQuietly(['status' => QuoteStatus::EXPIRED->value]);
            
            $quote->statusHistories()->create([
                'status' => QuoteStatus::EXPIRED->value,
                'comment' => 'Devis expiré automatiquement',
                'user_id' => null,
                'created_at' => now()
            ]);
        }
    }
}
