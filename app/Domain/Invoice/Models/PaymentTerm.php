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

class PaymentTerm extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'days',
        'description',
        'is_default',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'days' => 'integer',
        'is_default' => 'boolean',
    ];

    /**
     * Get the company that owns the payment term.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the clients that use this payment term.
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'payment_terms_id');
    }

    /**
     * Calculate the due date based on these payment terms.
     */
    public function calculateDueDate($fromDate = null): string
    {
        $date = $fromDate ? \Carbon\Carbon::parse($fromDate) : now();
        return $date->addDays($this->days);
    }

    /**
     * Get display name with days.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name . ' (' . $this->days . ' ' . trans_choice('days', $this->days) . ')';
    }

    /**
     * Scope a query to only include default payment terms.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
