<?php

namespace App\Domain\Invoice\Models;

use App\Domain\Company\Models\Company;
use App\Domain\Payment\Models\Payment;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
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
        'type',
        'is_online',
        'is_active',
        'settings',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_online' => 'boolean',
        'is_active' => 'boolean',
        'settings' => 'json',
    ];

    /**
     * Get the company that owns the payment method.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the payments that use this payment method.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Check if the payment method is set up for online payments.
     */
    public function getIsSetupForOnlineAttribute(): bool
    {
        if (!$this->is_online) {
            return false;
        }
        
        // Check if necessary settings are present
        switch ($this->type) {
            case 'card':
                return isset($this->settings['stripe_enabled']) && $this->settings['stripe_enabled'];
                
            case 'bank_transfer':
                return !empty($this->settings['iban']) && !empty($this->settings['bic']);
                
            default:
                return false;
        }
    }

    /**
     * Scope a query to only include active payment methods.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include online payment methods.
     */
    public function scopeOnline($query)
    {
        return $query->where('is_online', true);
    }

    /**
     * Scope a query to filter by payment method type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }
}
