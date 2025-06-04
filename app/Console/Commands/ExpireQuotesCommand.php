<?php

namespace App\Console\Commands;

use App\Domain\Quote\Models\Quote;
use App\Enums\QuoteStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireQuotesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quotes:expire 
                            {--dry-run : Run in dry-run mode without making changes}
                            {--days= : Number of days before considering quotes expired (overrides quote valid_until)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark expired quotes as expired and create status history entries';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $daysOverride = $this->option('days');
        
        $this->info('Starting quotes expiration process...');
        
        if ($isDryRun) {
            $this->warn('Running in DRY-RUN mode - no changes will be made');
        }

        // Query pour trouver les devis expirés
        $query = Quote::query()
            ->whereIn('status', [
                QuoteStatus::SENT->value,
                QuoteStatus::PENDING->value
            ]);

        if ($daysOverride) {
            // Utiliser le nombre de jours spécifié
            $expirationDate = now()->subDays($daysOverride);
            $query->where('quote_date', '<=', $expirationDate);
            $this->info("Looking for quotes older than {$daysOverride} days");
        } else {
            // Utiliser la date de validité de chaque devis
            $query->where('valid_until', '<', now());
            $this->info('Looking for quotes past their valid_until date');
        }

        $expiredQuotes = $query->with(['company', 'customer'])->get();

        if ($expiredQuotes->isEmpty()) {
            $this->info('No expired quotes found.');
            return 0;
        }

        $this->info("Found {$expiredQuotes->count()} expired quotes");

        // Tableau pour les statistiques
        $stats = [
            'expired' => 0,
            'errors' => 0
        ];

        // Traiter chaque devis expiré
        $progressBar = $this->output->createProgressBar($expiredQuotes->count());
        $progressBar->start();

        foreach ($expiredQuotes as $quote) {
            try {
                $this->processExpiredQuote($quote, $isDryRun);
                $stats['expired']++;
                
                $this->info("\n✓ Quote {$quote->quote_number} marked as expired");
                
            } catch (\Exception $e) {
                $stats['errors']++;
                $this->error("\n✗ Error processing quote {$quote->quote_number}: {$e->getMessage()}");
                
                Log::error('Error expiring quote', [
                    'quote_id' => $quote->id,
                    'quote_number' => $quote->quote_number,
                    'error' => $e->getMessage()
                ]);
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Afficher les statistiques
        $this->info('Expiration process completed:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Quotes processed', $expiredQuotes->count()],
                ['Quotes expired', $stats['expired']],
                ['Errors', $stats['errors']]
            ]
        );

        if (!$isDryRun) {
            Log::info('Quotes expiration process completed', [
                'total_processed' => $expiredQuotes->count(),
                'expired' => $stats['expired'],
                'errors' => $stats['errors']
            ]);
        }

        return 0;
    }

    /**
     * Process a single expired quote
     */
    private function processExpiredQuote(Quote $quote, bool $isDryRun): void
    {
        if ($isDryRun) {
            $this->line("Would expire quote {$quote->quote_number} (Valid until: {$quote->valid_until->format('Y-m-d')})");
            return;
        }

        // Mettre à jour le statut
        $quote->update(['status' => QuoteStatus::EXPIRED->value]);

        // Créer un historique de statut
        $quote->statusHistories()->create([
            'status' => QuoteStatus::EXPIRED->value,
            'comment' => 'Devis expiré automatiquement',
            'user_id' => null, // Système
            'created_at' => now()
        ]);

        // Log l'expiration
        Log::info('Quote expired automatically', [
            'quote_id' => $quote->id,
            'quote_number' => $quote->quote_number,
            'company_id' => $quote->company_id,
            'customer_id' => $quote->customer_id,
            'valid_until' => $quote->valid_until,
            'expired_at' => now()
        ]);
    }
}
