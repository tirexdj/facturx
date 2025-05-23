<?php

namespace App\Domain\Shared\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PhoneNumber extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'phoneable_type',
        'phoneable_id',
        'label',
        'country_code',
        'number',
        'extension',
        'is_default',
        'is_mobile',
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
        'is_mobile' => 'boolean',
    ];

    /**
     * Get the parent phoneable model.
     */
    public function phoneable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the full phone number as a string.
     */
    public function getFullNumberAttribute(): string
    {
        $phoneNumber = $this->country_code . ' ' . $this->number;
        
        if (!empty($this->extension)) {
            $phoneNumber .= ' ext. ' . $this->extension;
        }
        
        return $phoneNumber;
    }

    /**
     * Get the formatted phone number for display.
     */
    public function getFormattedNumberAttribute(): string
    {
        // This is a simple example. In a real application, you might want to use a library
        // like libphonenumber to format phone numbers according to each country's conventions.
        
        if ($this->country_code === '+33') {
            // Format for France
            $number = preg_replace('/[^0-9]/', '', $this->number);
            if (strlen($number) === 9 && $number[0] === '0') {
                $number = substr($number, 1);
            }
            
            if (strlen($number) === 9) {
                return "+33 " . substr($number, 0, 1) . " " . 
                       substr($number, 1, 2) . " " . 
                       substr($number, 3, 2) . " " . 
                       substr($number, 5, 2) . " " . 
                       substr($number, 7, 2);
            }
        }
        
        // Default formatting
        return $this->country_code . ' ' . $this->number;
    }
}
