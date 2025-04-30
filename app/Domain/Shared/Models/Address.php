<?php

namespace App\Domain\Shared\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'addressable_type',
        'addressable_id',
        'label',
        'address_line1',
        'address_line2',
        'address_line3',
        'postal_code',
        'city',
        'state_province',
        'country_code',
        'is_default',
        'is_billing',
        'is_shipping',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'boolean',
        'is_billing' => 'boolean',
        'is_shipping' => 'boolean',
    ];

    /**
     * Get the parent addressable model.
     */
    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the full address as a string.
     */
    public function getFullAddressAttribute(): string
    {
        $address = $this->address_line1;
        
        if (!empty($this->address_line2)) {
            $address .= ', ' . $this->address_line2;
        }
        
        if (!empty($this->address_line3)) {
            $address .= ', ' . $this->address_line3;
        }
        
        $address .= ', ' . $this->postal_code . ' ' . $this->city;
        
        if (!empty($this->state_province)) {
            $address .= ', ' . $this->state_province;
        }
        
        if ($this->country_code !== 'FR') {
            $address .= ', ' . $this->country_code;
        }
        
        return $address;
    }

    /**
     * Get the formatted single-line address.
     */
    public function getFormattedAddressAttribute(): string
    {
        $parts = [];
        
        if (!empty($this->address_line1)) $parts[] = $this->address_line1;
        if (!empty($this->address_line2)) $parts[] = $this->address_line2;
        if (!empty($this->address_line3)) $parts[] = $this->address_line3;
        if (!empty($this->postal_code) || !empty($this->city)) {
            $parts[] = trim($this->postal_code . ' ' . $this->city);
        }
        if (!empty($this->state_province)) $parts[] = $this->state_province;
        if (!empty($this->country_code) && $this->country_code !== 'FR') $parts[] = $this->country_code;
        
        return implode(', ', $parts);
    }
}
