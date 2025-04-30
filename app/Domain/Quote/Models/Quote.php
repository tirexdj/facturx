<?php

namespace App\Domain\Quote\Models;

use App\Domain\Company\Models\Company;
use App\Domain\Customer\Models\Client;
use App\Domain\Invoice\Models\Invoice;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

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
        'sent_at',
        'accepted_at',
        'rejected_at',
        'rejection_reason',
        'signature_data',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'validity_date' => 'date',
        'exchange_rate' => 'decimal:6',
        'subtotal_net' => 'decimal:5',
        'discount_value' => 'decimal:5',
        'discount_amount' => 'decimal:5',
        'total_net' => 'decimal:5',
        'total_tax' => 'decimal:5',
        'total_gross' => 'decimal:5',
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'signature_data' => 'json',
    ];

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
        return $this->status !== 'accepted' && $this->validity_date < now();
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
     * Scope a query to only include quotes with a specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include quotes that are valid.
     */
    public function scopeValid($query)
    {
        return $query->where('validity_date', '>=', now())
                     ->where('status', '!=', 'rejected')
                     ->where('status', '!=', 'expired');
    }

    /**
     * Scope a query to only include quotes that are expired.
     */
    public function scopeExpired($query)
    {
        return $query->where('validity_date', '<', now())
                     ->whereNotIn('status', ['accepted', 'rejected']);
    }
}
