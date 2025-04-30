<?php

namespace App\Domain\EReporting\Models;

use App\Domain\Company\Models\Company;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EReportingTransmission extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'e_reporting_transmissions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'transmission_type',
        'period_start',
        'period_end',
        'submission_date',
        'status',
        'data',
        'response_data',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'submission_date' => 'datetime',
        'data' => 'json',
        'response_data' => 'json',
    ];

    /**
     * Get the company that owns the transmission.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Check if the transmission has been submitted.
     */
    public function getIsSubmittedAttribute(): bool
    {
        return !empty($this->submission_date);
    }

    /**
     * Check if the transmission was successful.
     */
    public function getIsSuccessfulAttribute(): bool
    {
        return $this->status === 'accepted';
    }

    /**
     * Check if the transmission failed.
     */
    public function getHasFailedAttribute(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Scope a query to only include transmissions of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('transmission_type', $type);
    }

    /**
     * Scope a query to only include B2C transmissions.
     */
    public function scopeB2C($query)
    {
        return $query->where('transmission_type', 'B2C');
    }

    /**
     * Scope a query to only include international B2B transmissions.
     */
    public function scopeB2BInternational($query)
    {
        return $query->where('transmission_type', 'B2B_international');
    }

    /**
     * Scope a query to only include transmissions with a specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include submitted transmissions.
     */
    public function scopeSubmitted($query)
    {
        return $query->whereNotNull('submission_date');
    }

    /**
     * Scope a query to only include pending transmissions.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include transmissions for a specific period.
     */
    public function scopeForPeriod($query, $start, $end)
    {
        return $query->where('period_start', $start)
                     ->where('period_end', $end);
    }
}
