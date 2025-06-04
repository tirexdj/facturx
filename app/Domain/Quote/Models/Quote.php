<?php

namespace App\Domain\Quote\Models;

use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use App\Domain\Company\Models\Company;
use App\Domain\Customer\Models\Client;
use App\Domain\Invoice\Models\Invoice;
use Illuminate\Database\Eloquent\Model;
use App\Domain\Shared\Enums\QuoteStatus;
use App\Domain\Quote\Events\QuoteCreated;
use App\Domain\Shared\Enums\DocumentType;
use App\Domain\Quote\Events\QuoteAccepted;
use App\Domain\Shared\Models\StatusHistory;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Domain\Quote\Events\QuoteStatusChanged;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Quote extends Model implements HasMedia
{
    use HasFactory, HasUuids, SoftDeletes, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'client_id',
        'quote_number',
        'reference',
        'title',
        'introduction',
        'date',
        'validity_date',
        'status',
        'currency_code',
        'exchange_rate',
        'subtotal_net',
        'discount_type',
        'discount_value',
        'discount_amount',
        'total_net',
        'total_tax',
        'total_gross',
        'notes',
        'terms',
        'footer',
        'pdf_path',
        'consultation_token',
        'sent_at',
        'accepted_at',
        'rejected_at',
        'rejection_reason',
        'signature_data',
        'created_by',
        'updated_by',
        // Nouveaux champs pour la phase 6
        'is_purchase_order',
        'is_billable',
        'deposit_percentage',
        'deposit_amount',
        'payment_terms',
        'template_name',
        'template_config',
        'legal_mentions',
        'internal_notes',
        'public_notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'validity_date' => 'date',
        'status' => QuoteStatus::class,
        'exchange_rate' => 'decimal:6',
        'subtotal_net' => 'decimal:5',
        'discount_value' => 'decimal:5',
        'discount_amount' => 'decimal:5',
        'total_net' => 'decimal:5',
        'total_tax' => 'decimal:5',
        'total_gross' => 'decimal:5',
        'deposit_percentage' => 'decimal:2',
        'deposit_amount' => 'decimal:5',
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'signature_data' => 'json',
        'template_config' => 'json',
        'legal_mentions' => 'json',
        'is_purchase_order' => 'boolean',
        'is_billable' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Quote $quote) {
            if (empty($quote->quote_number)) {
                $quote->quote_number = $quote->generateQuoteNumber();
            }
            
            if (empty($quote->consultation_token)) {
                $quote->consultation_token = Str::random(64);
            }

            if (empty($quote->status)) {
                $quote->status = QuoteStatus::DRAFT;
            }

            if (empty($quote->date)) {
                $quote->date = now()->toDateString();
            }

            if (empty($quote->validity_date)) {
                $validityDays = $quote->company->quote_validity_days ?? 30;
                $quote->validity_date = now()->addDays($validityDays);
            }
        });

        static::created(function (Quote $quote) {
            event(new QuoteCreated($quote));
        });

        static::updating(function (Quote $quote) {
            // Déclencher un événement si le statut change
            if ($quote->isDirty('status')) {
                $oldStatus = $quote->getOriginal('status');
                $newStatus = $quote->status;
                
                if ($oldStatus !== null && $oldStatus !== $newStatus) {
                    $oldStatusEnum = is_string($oldStatus) ? QuoteStatus::from($oldStatus) : $oldStatus;
                    event(new QuoteStatusChanged($quote, $oldStatusEnum, $newStatus));
                    
                    // Événement spécifique pour l'acceptation
                    if ($newStatus === QuoteStatus::ACCEPTED) {
                        event(new QuoteAccepted($quote, null, $quote->signature_data));
                    }
                }
            }
        });
    }

    /**
     * Register media collections for the quote.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('pdf')
            ->singleFile();
            
        $this->addMediaCollection('attachments');
        
        $this->addMediaCollection('signature')
            ->singleFile();
    }

    /**
     * Get the company that owns the quote.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the client that the quote belongs to.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the lines for the quote.
     */
    public function lines(): HasMany
    {
        return $this->hasMany(QuoteLine::class);
    }

    /**
     * Get the invoice that was created from this quote.
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    /**
     * Get the status history for the quote.
     */
    public function statusHistory(): MorphMany
    {
        return $this->morphMany(StatusHistory::class, 'model');
    }

    /**
     * Generate quote number
     */
    public function generateQuoteNumber(): string
    {
        $prefix = $this->is_purchase_order ? 'BC' : 'DEV';
        $year = now()->year;
        
        $lastNumber = static::where('company_id', $this->company_id)
            ->whereYear('date', $year)
            ->where('quote_number', 'like', "{$prefix}-{$year}-%")
            ->count();

        return sprintf('%s-%d-%05d', $prefix, $year, $lastNumber + 1);
    }

    /**
     * Update quote status with validation and event dispatch
     */
    public function updateStatus(QuoteStatus $newStatus, ?string $reason = null, ?int $userId = null): bool
    {
        if (!$this->status->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException(
                "Cannot transition from {$this->status->label()} to {$newStatus->label()}"
            );
        }

        $oldStatus = $this->status;
        $this->status = $newStatus;

        // Update related dates
        switch ($newStatus) {
            case QuoteStatus::SENT:
                $this->sent_at = now();
                break;
            case QuoteStatus::ACCEPTED:
                $this->accepted_at = now();
                break;
            case QuoteStatus::REJECTED:
                $this->rejected_at = now();
                if ($reason) {
                    $this->rejection_reason = $reason;
                }
                break;
        }

        $result = $this->save();

        if ($result) {
            // Log status change
            $this->statusHistory()->create([
                'old_status' => $oldStatus->value,
                'new_status' => $newStatus->value,
                'reason' => $reason,
                'user_id' => $userId ?? auth()->id(),
                'created_at' => now(),
            ]);
        }

        return $result;
    }

    /**
     * Calculate totals for the quote
     */
    public function calculateTotals(): void
    {
        $subtotal = $this->lines->sum(function ($line) {
            return $line->quantity * $line->unit_price_net;
        });

        $discountAmount = 0;
        if ($this->discount_type === 'percentage' && $this->discount_value > 0) {
            $discountAmount = $subtotal * ($this->discount_value / 100);
        } elseif ($this->discount_type === 'amount' && $this->discount_amount > 0) {
            $discountAmount = $this->discount_amount;
        }

        $netTotal = $subtotal - $discountAmount;

        $taxTotal = $this->lines->sum(function ($line) use ($discountAmount, $subtotal) {
            $lineNet = $line->quantity * $line->unit_price_net;
            if ($discountAmount > 0 && $subtotal > 0) {
                $lineNet -= ($lineNet / $subtotal) * $discountAmount;
            }
            return $lineNet * ($line->vatRate->rate ?? 0) / 100;
        });

        $this->subtotal_net = $subtotal;
        $this->discount_amount = $discountAmount;
        $this->total_net = $netTotal;
        $this->total_tax = $taxTotal;
        $this->total_gross = $netTotal + $taxTotal;

        // Calculate deposit if applicable
        if ($this->deposit_percentage > 0) {
            $this->deposit_amount = $this->total_gross * ($this->deposit_percentage / 100);
        }
    }

    /**
     * Check if the quote has been converted to an invoice.
     */
    public function getIsConvertedAttribute(): bool
    {
        return $this->invoice()->exists();
    }

    /**
     * Check if the quote is expired.
     */
    public function getIsExpiredAttribute(): bool
    {
        return !$this->status->isFinal() && $this->validity_date < now();
    }

    /**
     * Get the optional lines for the quote.
     */
    public function getOptionalLinesAttribute()
    {
        return $this->lines()->where('is_optional', true)->get();
    }

    /**
     * Get the required lines for the quote.
     */
    public function getRequiredLinesAttribute()
    {
        return $this->lines()->where('is_optional', false)->get();
    }

    /**
     * Get document type
     */
    public function getDocumentTypeAttribute(): DocumentType
    {
        return $this->is_purchase_order ? DocumentType::PURCHASE_ORDER : DocumentType::QUOTE;
    }

    /**
     * Get consultation URL
     */
    public function getConsultationUrlAttribute(): string
    {
        return route('quotes.consultation', ['token' => $this->consultation_token]);
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute(): string
    {
        return $this->status->color();
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
    }

    /**
     * Get status icon
     */
    public function getStatusIconAttribute(): string
    {
        return $this->status->icon();
    }

    /**
     * Check if quote can be edited
     */
    public function canBeEdited(): bool
    {
        return $this->status->isEditable();
    }

    /**
     * Check if quote can be sent
     */
    public function canBeSent(): bool
    {
        return $this->status->canBeSent();
    }

    /**
     * Check if quote can be converted to invoice
     */
    public function canBeConverted(): bool
    {
        return $this->status->canBeConverted() && !$this->is_converted;
    }

    /**
     * Check if quote requires deposit
     */
    public function requiresDeposit(): bool
    {
        return $this->deposit_percentage > 0 || $this->deposit_amount > 0;
    }

    /**
     * Get formatted quote number
     */
    public function getFormattedNumberAttribute(): string
    {
        return $this->quote_number;
    }

    /**
     * Scope a query to only include quotes with a specific status.
     */
    public function scopeWithStatus($query, QuoteStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include quotes that are valid.
     */
    public function scopeValid($query)
    {
        return $query->where('validity_date', '>=', now())
                     ->whereNotIn('status', [QuoteStatus::REJECTED, QuoteStatus::EXPIRED, QuoteStatus::CANCELLED]);
    }

    /**
     * Scope a query to only include quotes that are expired.
     */
    public function scopeExpired($query)
    {
        return $query->where('validity_date', '<', now())
                     ->whereNotIn('status', [QuoteStatus::ACCEPTED, QuoteStatus::REJECTED, QuoteStatus::CANCELLED]);
    }

    /**
     * Scope a query to only include purchase orders.
     */
    public function scopePurchaseOrders($query)
    {
        return $query->where('is_purchase_order', true);
    }

    /**
     * Scope a query to only include billable quotes.
     */
    public function scopeBillable($query)
    {
        return $query->where('is_billable', true);
    }

    /**
     * Scope a query to only include quotes pending action.
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [QuoteStatus::SENT, QuoteStatus::PENDING]);
    }

    /**
     * Scope a query to filter by company.
     */
    public function scopeForCompany($query, Company $company)
    {
        return $query->where('company_id', $company->id);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateRange($query, $start, $end)
    {
        return $query->whereBetween('date', [$start, $end]);
    }
}
