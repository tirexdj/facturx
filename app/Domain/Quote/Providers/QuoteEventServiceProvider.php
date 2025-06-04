<?php

namespace App\Domain\Quote\Providers;

use App\Domain\Quote\Events\QuoteCreated;
use App\Domain\Quote\Events\QuoteStatusChanged;
use App\Domain\Quote\Events\QuoteSent;
use App\Domain\Quote\Events\QuoteAccepted;
use App\Domain\Quote\Listeners\HandleQuoteCreated;
use App\Domain\Quote\Listeners\HandleQuoteStatusChange;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class QuoteEventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        QuoteCreated::class => [
            HandleQuoteCreated::class,
        ],
        
        QuoteStatusChanged::class => [
            HandleQuoteStatusChange::class,
        ],

        QuoteSent::class => [
            // Peut être ajouté plus tard pour l'envoi d'emails
        ],

        QuoteAccepted::class => [
            // Peut être ajouté plus tard pour des actions spécifiques
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();
    }
}
