<?php

namespace App\Domain\Product\Models;

use App\Domain\Company\Models\Company;
use Database\Factories\Domain\Product\Models\ServiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Service extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'category_id',
        'name',
        'description',
        'unit_price',
        'cost_price',
        'vat_rate',
        'unit',
        'duration',
        'is_recurring',
        'recurring_period',
        'setup_fee',
        'is_active',
        'options',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'unit_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'setup_fee' => 'decimal:2',
        'duration' => 'integer',
        'is_recurring' => 'boolean',
        'is_active' => 'boolean',
        'options' => 'json',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return ServiceFactory::new();
    }

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'unit_price', 'cost_price', 'vat_rate', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the company that owns the service.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the category that the service belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Calculate the margin amount.
     */
    public function getMarginAttribute(): ?float
    {
        if (!$this->cost_price) {
            return null;
        }
        
        return $this->unit_price - $this->cost_price;
    }

    /**
     * Calculate the margin as a percentage.
     */
    public function getMarginPercentageAttribute(): ?float
    {
        if (!$this->cost_price || $this->cost_price == 0) {
            return null;
        }
        
        return round((($this->unit_price - $this->cost_price) / $this->cost_price) * 100, 2);
    }

    /**
     * Calculate the total price including setup fee.
     */
    public function getTotalPriceWithSetupAttribute(): ?float
    {
        if (!$this->setup_fee) {
            return null;
        }
        
        return $this->unit_price + $this->setup_fee;
    }

    /**
     * Scope a query to only include active services.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include recurring services.
     */
    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    /**
     * Scope a query to only include services in a specific category.
     */
    public function scopeInCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope a query to filter services by company.
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope a query to filter services by unit.
     */
    public function scopeByUnit($query, $unit)
    {
        return $query->where('unit', $unit);
    }
}
