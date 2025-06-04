<?php

namespace App\Domain\Product\Models;

use App\Domain\Company\Models\Company;
use Database\Factories\Domain\Product\Models\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Product extends Model
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
        'reference',
        'unit_price',
        'cost_price',
        'vat_rate',
        'unit',
        'weight',
        'dimensions',
        'barcode',
        'stock_quantity',
        'stock_alert_threshold',
        'is_active',
        'attributes',
        'variants',
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
        'weight' => 'decimal:3',
        'stock_quantity' => 'integer',
        'stock_alert_threshold' => 'integer',
        'is_active' => 'boolean',
        'attributes' => 'json',
        'variants' => 'json',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return ProductFactory::new();
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
     * Get the company that owns the product.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the category that the product belongs to.
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
     * Check if the product is in stock.
     */
    public function getInStockAttribute(): bool
    {
        if ($this->stock_quantity === null) {
            return true; // No stock management
        }
        
        return $this->stock_quantity > 0;
    }

    /**
     * Check if the product is low on stock.
     */
    public function getLowStockAttribute(): bool
    {
        if ($this->stock_quantity === null || $this->stock_alert_threshold === null) {
            return false;
        }
        
        return $this->stock_quantity <= $this->stock_alert_threshold;
    }

    /**
     * Scope a query to only include active products.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include products in a specific category.
     */
    public function scopeInCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope a query to filter products by company.
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
