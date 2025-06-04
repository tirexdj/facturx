<?php

namespace App\Domain\Quote\Events;

use App\Domain\Quote\Models\Quote;
use App\Domain\Shared\Enums\QuoteStatus;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuoteStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Quote $quote,
        public QuoteStatus $oldStatus,
        public QuoteStatus $newStatus,
        public ?string $reason = null,
        public ?int $userId = null
    ) {
        $this->userId = $userId ?? auth()->id();
    }
}
