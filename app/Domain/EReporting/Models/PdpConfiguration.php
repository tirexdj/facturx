<?php

namespace App\Domain\EReporting\Models;

use App\Domain\Company\Models\Company;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PdpConfiguration extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pdp_configurations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'pdp_code',
        'pdp_name',
        'api_key',
        'api_secret',
        'endpoint_url',
        'configuration',
        'is_default',
        'is_active',
        'test_mode',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'configuration' => 'json',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'test_mode' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'api_key',
        'api_secret',
    ];

    /**
     * Get the company that owns the PDP configuration.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Check if the configuration has valid API credentials.
     */
    public function getHasCredentialsAttribute(): bool
    {
        return !empty($this->api_key) && !empty($this->api_secret);
    }

    /**
     * Check if the configuration is ready to use.
     */
    public function getIsReadyAttribute(): bool
    {
        return $this->is_active && $this->hasCredentials && !empty($this->endpoint_url);
    }

    /**
     * Scope a query to only include active configurations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include default configurations.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope a query to only include test mode configurations.
     */
    public function scopeTestMode($query)
    {
        return $query->where('test_mode', true);
    }

    /**
     * Scope a query to only include production mode configurations.
     */
    public function scopeProductionMode($query)
    {
        return $query->where('test_mode', false);
    }

    /**
     * Scope a query to filter by PDP code.
     */
    public function scopeByPdpCode($query, $code)
    {
        return $query->where('pdp_code', $code);
    }

    /**
     * Set this configuration as the default for the company.
     */
    public function setAsDefault()
    {
        // First, remove default status from all other configurations
        self::where('company_id', $this->company_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);
        
        // Then set this one as default
        $this->is_default = true;
        $this->save();
        
        return $this;
    }
}
