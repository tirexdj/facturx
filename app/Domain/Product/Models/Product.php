<?php

namespace App\Domain\Product\Models;

use App\Domain\Company\Models\Company;
use App\Domain\Customer\Models\Category;
use App\Domain\Invoice\Models\InvoiceLine;
use App\Domain\Quote\Models\QuoteLine;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use HasFactory, HasUuids, SoftDeletes, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'reference',
        'barcode',
        'name',
        'slug',
        'description',
        'long_description',
        'price_net',
        'cost_price',
        'vat_rate_id',
        'unit_id',
        'category_id',
        'stock_management',
        'stock_quantity',
        'stock_alert_threshold',
        'weight',
        'dimensions',
        'is_active',
        'is_featured',
        'tags',
        'custom_fields',
        'seo_title',
        'seo_description',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price_net' => 'decimal:5',
        'cost_price' => 'decimal:5',
        'stock_quantity' => 'decimal:3',
        'stock_alert_threshold' => 'decimal:3',
        'weight' => 'decimal:3',
        'dimensions' => 'json',
        'stock_management' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'tags' => 'json',
        'custom_fields' => 'json',
    ];

    /**
     * Register media collections for the product.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
        
        $this->addMediaCollection('thumbnail')
            ->singleFile();
            
        $this->addMediaCollection('documents');
    }

    /**
     * Get the company that owns the product.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the VAT rate that the product belongs to.
     */
    public function vatRate(): BelongsTo
    {
        return $this->belongsTo(VatRate::class);
    }

    /**
     * Get the unit that the product belongs to.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the category that the product belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the variants for the product.
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Get the quote lines that include this product.
     */
    public function quoteLines(): HasMany
    {
        return $this->hasMany(QuoteLine::class);
    }

    /**
     * Get the invoice lines that include this product.
     */
    public function invoiceLines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    /**
     * Calculate the price with VAT.
     */
    public function getPriceGrossAttribute()
    {
        if ($this->relationLoaded('vatRate')) {
            return $this->price_net * (1 + $this->vatRate->rate / 100);
        }
        
        return $this->price_net;
    }

    /**
     * Calculate the margin as a percentage.
     */
    public function getMarginPercentAttribute()
    {
        if (empty($this->cost_price) || $this->cost_price == 0) {
            return null;
        }
        
        return (($this->price_net - $this->cost_price) / $this->cost_price) * 100;
    }

    /**
     * Check if the product is in stock.
     */
    public function getInStockAttribute(): bool
    {
        if (!$this->stock_management) {
            return true;
        }
        
        return $this->stock_quantity > 0;
    }

    /**
     * Check if the product is low on stock.
     */
    public function getLowStockAttribute(): bool
    {
        if (!$this->stock_management || empty($this->stock_alert_threshold)) {
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
     * Scope a query to only include featured products.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
    
    /**
     * Scope a query to only include products in a specific category.
     */
    public function scopeInCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }
    
    /**
     * Scope a query to filter products by tag.
     */
    public function scopeWithTag($query, $tag)
    {
        return $query->where('tags', 'like', '%' . $tag . '%');
    }
}
