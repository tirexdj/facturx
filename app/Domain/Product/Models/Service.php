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

class Service extends Model implements HasMedia
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
        'name',
        'slug',
        'description',
        'long_description',
        'price_net',
        'cost_price',
        'vat_rate_id',
        'unit_id',
        'duration',
        'is_recurring',
        'recurrence_interval',
        'category_id',
        'is_active',
        'tags',
        'custom_fields',
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
        'duration' => 'integer',
        'is_recurring' => 'boolean',
        'is_active' => 'boolean',
        'tags' => 'json',
        'custom_fields' => 'json',
    ];

    /**
     * Register media collections for the service.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
        
        $this->addMediaCollection('thumbnail')
            ->singleFile();
            
        $this->addMediaCollection('documents');
    }

    /**
     * Get the company that owns the service.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the VAT rate that the service belongs to.
     */
    public function vatRate(): BelongsTo
    {
        return $this->belongsTo(VatRate::class);
    }

    /**
     * Get the unit that the service belongs to.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the category that the service belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the quote lines that include this service.
     */
    public function quoteLines(): HasMany
    {
        return $this->hasMany(QuoteLine::class);
    }

    /**
     * Get the invoice lines that include this service.
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
     * Scope a query to filter services by tag.
     */
    public function scopeWithTag($query, $tag)
    {
        return $query->where('tags', 'like', '%' . $tag . '%');
    }
}
