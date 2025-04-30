<?php

namespace App\Domain\Invoice\Models;

use App\Domain\Company\Models\Company;
use App\Domain\Customer\Models\Client;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceRecurrence extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'client_id',
        'template_invoice_id',
        'name',
        'frequency',
        'interval',
        'start_date',
        'end_date',
        'next_date',
        'day_of_month',
        'month_of_year',
        'status',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_date' => 'date',
        'interval' => 'integer',
        'day_of_month' => 'integer',
        'month_of_year' => 'integer',
    ];

    /**
     * Get the company that owns the recurrence.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the client that the recurrence belongs to.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the template invoice for the recurrence.
     */
    public function templateInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'template_invoice_id');
    }

    /**
     * Get the invoices generated from this recurrence.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'recurrence_id');
    }

    /**
     * Check if the recurrence is active.
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the recurrence has an end date.
     */
    public function getHasEndDateAttribute(): bool
    {
        return !empty($this->end_date);
    }

    /**
     * Check if the recurrence is completed.
     */
    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed' || 
               ($this->hasEndDate && $this->end_date < now());
    }

    /**
     * Scope a query to only include active recurrences.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include recurrences due for generation.
     */
    public function scopeDueForGeneration($query)
    {
        return $query->where('status', 'active')
                     ->where('next_date', '<=', now())
                     ->where(function($q) {
                         $q->whereNull('end_date')
                           ->orWhere('end_date', '>=', now());
                     });
    }

    /**
     * Calculate the next date for the recurrence.
     */
    public function calculateNextDate(): ?string
    {
        if (!$this->isActive) {
            return null;
        }
        
        $date = $this->next_date ?? $this->start_date;
        
        if (empty($date)) {
            return null;
        }
        
        switch ($this->frequency) {
            case 'daily':
                return $date->addDays($this->interval);
                
            case 'weekly':
                return $date->addWeeks($this->interval);
                
            case 'monthly':
                $next = $date->addMonths($this->interval);
                
                if ($this->day_of_month) {
                    // Try to set to specific day of month, but don't exceed month end
                    $daysInMonth = $next->daysInMonth;
                    $day = min($this->day_of_month, $daysInMonth);
                    $next->setDay($day);
                }
                
                return $next;
                
            case 'quarterly':
                return $date->addMonths($this->interval * 3);
                
            case 'yearly':
                $next = $date->addYears($this->interval);
                
                if ($this->month_of_year && $this->day_of_month) {
                    // Try to set specific month and day
                    $next->setMonth($this->month_of_year);
                    
                    // Don't exceed month end
                    $daysInMonth = $next->daysInMonth;
                    $day = min($this->day_of_month, $daysInMonth);
                    $next->setDay($day);
                }
                
                return $next;
                
            default:
                return null;
        }
    }
}
