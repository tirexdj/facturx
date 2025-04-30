<?php

namespace App\Domain\Shared\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Email extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'emailable_type',
        'emailable_id',
        'label',
        'email',
        'is_default',
        'is_verified',
        'verification_token',
        'verified_at',
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
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    /**
     * Get the parent emailable model.
     */
    public function emailable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the display name for the email address.
     */
    public function getDisplayNameAttribute(): string
    {
        if (!empty($this->label)) {
            return $this->label . ' (' . $this->email . ')';
        }
        
        return $this->email;
    }

    /**
     * Scope a query to only include verified emails.
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope a query to only include default emails.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
