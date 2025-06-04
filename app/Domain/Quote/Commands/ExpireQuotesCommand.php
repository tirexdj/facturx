<?php

namespace App\Domain\Quote\Commands;

use App\Domain\Quote\Services\QuoteService;
use Illuminate\Console\Command;

class ExpireQuotesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quotes:expire
                          {--dry-run : Show quotes that would be expired without actually expiring them}
                          {--company= : Expire quotes only for a specific company ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire quotes that have passed their validity date';

    public function __construct(
        protected QuoteService $quoteService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Searching for expired quotes...');

        if ($this->option('dry-run')) {
            return $this->handleDryRun();
        }

        return $this->handleExpiration();
    }

    protected function handleDryRun(): int
    {
        $query = \App\Domain\Quote\Models\Quote::expired();
        
        if ($companyId = $this->option('company')) {
            $query->where('company_id', $companyId);
        }

        $expiredQuotes = $query->get();

        if ($expiredQuotes->isEmpty()) {
            $this->info('✅ No quotes to expire found.');
            return Command::SUCCESS;
        }

        $this->info("📋 Found {$expiredQuotes->count()} quotes that would be expired:");

        $headers = ['Quote Number', 'Company', 'Client', 'Validity Date', 'Status', 'Total'];
        $rows = [];

        foreach ($expiredQuotes as $quote) {
            $rows[] = [
                $quote->quote_number,
                $quote->company->name,
                $quote->client->name,
                $quote->validity_date->format('d/m/Y'),
                $quote->status->label(),
                number_format($quote->total_gross, 2) . ' €',
            ];
        }

        $this->table($headers, $rows);
        
        $this->warn('🚨 This was a dry run. Use --dry-run=false to actually expire these quotes.');

        return Command::SUCCESS;
    }

    protected function handleExpiration(): int
    {
        try {
            $expiredCount = $this->quoteService->expireQuotes();

            if ($expiredCount === 0) {
                $this->info('✅ No quotes to expire found.');
                return Command::SUCCESS;
            }

            $this->info("✅ Successfully expired {$expiredCount} quote(s).");
            
            // Log l'action
            \Illuminate\Support\Facades\Log::info('Quotes expired via command', [
                'expired_count' => $expiredCount,
                'executed_by' => 'console'
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Error expiring quotes: {$e->getMessage()}");
            
            \Illuminate\Support\Facades\Log::error('Quote expiration command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }
}
