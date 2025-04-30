<?php

namespace App\Domain\Product\Models;

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

class ProductVariant extends Model implements HasMedia
{
    use HasFactory, HasUuids, SoftDeletes, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'barcode',
        'price_net_modifier',
        'price_net_override',
        'stock_quantity',
        'attributes',
        'is_active',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price_net_modifier' => 'decimal:5',
        'price_net_override' => 'decimal:5',
        'stock_quantity' => 'decimal:3',
        'attributes' => 'json',
        'is_active' => 'boolean',
    ];

    /**
     * Register media collections for the product variant.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
        
        $this->addMediaCollection('thumbnail')
            ->singleFile();
    }

    /**
     * Get the product that owns the variant.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the quote lines that include this product variant.
     */
    public function quoteLines(): HasMany
    {
        return $this->hasMany(QuoteLine::class);
    }

    /**
     * Get the invoice lines that include this product variant.
     */
    public function invoiceLines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    /**
     * Calculate the effective price of this variant.
     */
    public function getPriceNetAttribute()
    {
        if ($this->price_net_override !== null) {
            return $this->price_net_override;
        }
        
        if ($this->relationLoaded('product')) {
            $basePrice = $this->product->price_net;
            
            if ($this->price_net_modifier !== null) {
                return $basePrice + $this->price_net_modifier;
            }
            
            return $basePrice;
        }
        
        return null;
    }

    /**
     * Scope a query to only include active variants.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include variants with a specific attribute value.
     */
    public function scopeWithAttribute($query, $key, $value)
    {
        return $query->whereJsonContains("attributes->$key", $value);
    }
    
    /**
     * Get the full variant name including the product name.
     */
    public function getFullNameAttribute(): string
    {
        if ($this->relationLoaded('product')) {
            return $this->product->name . ' - ' . $this->name;
        }
        
        return $this->name;
    }
}
