<?php

namespace App\Domain\Invoice\Models;

use App\Domain\Company\Models\Company;
use App\Domain\Customer\Models\Client;
use App\Domain\Payment\Models\Payment;
use App\Domain\Quote\Models\Quote;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Invoice extends Model implements HasMedia
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
        'quote_id',
        'invoice_number',
        'reference',
        'title',
        'introduction',
        'date',
        'due_date',
        'status',
        'e_invoice_status',
        'payment_status',
        'currency_code',
        'exchange_rate',
        'subtotal_net',
        'discount_type',
        'discount_value',
        'discount_amount',
        'total_net',
        'total_tax',
        'total_gross',
        'amount_paid',
        'amount_due',
        'notes',
        'terms',
        'footer',
        'pdf_path',
        'sent_at',
        'paid_at',
        'e_invoice_format',
        'e_invoice_data',
        'e_invoice_path',
        'e_reporting_status',
        'e_reporting_transmitted_at',
        'is_recurring',
        'recurrence_id',
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
        'due_date' => 'date',
        'exchange_rate' => 'decimal:6',
        'subtotal_net' => 'decimal:5',
        'discount_value' => 'decimal:5',
        'discount_amount' => 'decimal:5',
        'total_net' => 'decimal:5',
        'total_tax' => 'decimal:5',
        'total_gross' => 'decimal:5',
        'amount_paid' => 'decimal:5',
        'amount_due' => 'decimal:5',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
        'e_invoice_data' => 'json',
        'e_reporting_transmitted_at' => 'datetime',
        'is_recurring' => 'boolean',
    ];

    /**
     * Register media collections for the invoice.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('pdf')
            ->singleFile();
            
        $this->addMediaCollection('e_invoice')
            ->singleFile();
            
        $this->addMediaCollection('attachments');
    }

    /**
     * Get the company that owns the invoice.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the client that the invoice belongs to.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the quote that the invoice was created from.
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /**
     * Get the lines for the invoice.
     */
    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    /**
     * Get the payments for the invoice.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the e-invoicing logs for the invoice.
     */
    public function eInvoicingLogs(): HasMany
    {
        return $this->hasMany(EInvoicingLog::class);
    }

    /**
     * Get the recurrence that the invoice belongs to.
     */
    public function recurrence(): BelongsTo
    {
        return $this->belongsTo(InvoiceRecurrence::class, 'recurrence_id');
    }

    /**
     * Check if the invoice is overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        return in_array($this->status, ['sent', 'partial']) && $this->due_date < now();
    }

    /**
     * Get the number of days the invoice is overdue.
     */
    public function getDaysOverdueAttribute(): int
    {
        if (!$this->isOverdue) {
            return 0;
        }
        
        return now()->diffInDays($this->due_date);
    }

    /**
     * Get the payment status of the invoice.
     */
    public function getPaymentStatusAttribute(): string
    {
        if ($this->amount_paid >= $this->total_gross) {
            return 'paid';
        }
        
        if ($this->amount_paid > 0) {
            return 'partial';
        }
        
        if ($this->isOverdue) {
            return 'overdue';
        }
        
        return 'unpaid';
    }

    /**
     * Scope a query to only include invoices with a specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include overdue invoices.
     */
    public function scopeOverdue($query)
    {
        return $query->whereIn('status', ['sent', 'partial'])
                     ->where('due_date', '<', now());
    }

    /**
     * Scope a query to only include invoices due this month.
     */
    public function scopeDueThisMonth($query)
    {
        return $query->whereIn('status', ['sent', 'partial'])
                     ->whereMonth('due_date', now()->month)
                     ->whereYear('due_date', now()->year);
    }

    /**
     * Scope a query to only include paid invoices.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope a query to only include unpaid invoices.
     */
    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['sent', 'partial']);
    }

    /**
     * Scope a query to only include invoices with recurring status.
     */
    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }
}
