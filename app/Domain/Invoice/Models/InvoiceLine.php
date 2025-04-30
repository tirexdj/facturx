<?php

namespace App\Domain\Invoice\Models;

use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Product\Models\Service;
use App\Domain\Product\Models\Unit;
use App\Domain\Product\Models\VatRate;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceLine extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'invoice_id',
        'line_type',
        'product_id',
        'service_id',
        'product_variant_id',
        'title',
        'description',
        'quantity',
        'unit_id',
        'unit_price_net',
        'vat_rate_id',
        'discount_type',
        'discount_value',
        'discount_amount',
        'subtotal_net',
        'tax_amount',
        'total_net',
        'position',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'decimal:5',
        'unit_price_net' => 'decimal:5',
        'discount_value' => 'decimal:5',
        'discount_amount' => 'decimal:5',
        'subtotal_net' => 'decimal:5',
        'tax_amount' => 'decimal:5',
        'total_net' => 'decimal:5',
        'position' => 'integer',
    ];

    /**
     * Get the invoice that owns the line.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the product that the line represents.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the service that the line represents.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the product variant that the line represents.
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Get the unit for the line.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the VAT rate for the line.
     */
    public function vatRate(): BelongsTo
    {
        return $this->belongsTo(VatRate::class);
    }

    /**
     * Calculate the unit price with VAT.
     */
    public function getUnitPriceGrossAttribute()
    {
        if ($this->relationLoaded('vatRate')) {
            return $this->unit_price_net * (1 + $this->vatRate->rate / 100);
        }
        
        return $this->unit_price_net;
    }

    /**
     * Calculate the total price with VAT.
     */
    public function getTotalGrossAttribute()
    {
        return $this->total_net + $this->tax_amount;
    }

    /**
     * Scope a query to order by position.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }
}
