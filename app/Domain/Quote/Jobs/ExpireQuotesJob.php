<?php

namespace App\Domain\Quote\Jobs;

use App\Domain\Quote\Services\QuoteService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExpireQuotesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle(QuoteService $quoteService): void
    {
        Log::info('Starting automatic quote expiration job');

        try {
            $expiredCount = $quoteService->expireQuotes();
            
            Log::info('Quote expiration job completed', [
                'expired_count' => $expiredCount
            ]);

            if ($expiredCount > 0) {
                // Optionnel : notifier les équipes commerciales
                // event(new QuotesExpired($expiredCount));
            }

        } catch (\Exception $e) {
            Log::error('Error during quote expiration job', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Quote expiration job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
