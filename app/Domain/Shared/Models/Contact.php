<?php

namespace App\Domain\Shared\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Contact extends Model implements HasMedia
{
    use HasFactory, HasUuids, SoftDeletes, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'contactable_type',
        'contactable_id',
        'first_name',
        'last_name',
        'job_title',
        'department',
        'is_primary',
        'notes',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_primary' => 'boolean',
    ];

    /**
     * Register media collections for the contact.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile();
    }

    /**
     * Get the parent contactable model.
     */
    public function contactable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get all of the contact's phone numbers.
     */
    public function phoneNumbers(): MorphMany
    {
        return $this->morphMany(PhoneNumber::class, 'phoneable');
    }

    /**
     * Get all of the contact's email addresses.
     */
    public function emails(): MorphMany
    {
        return $this->morphMany(Email::class, 'emailable');
    }

    /**
     * Get the contact's full name.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the contact's default email address.
     */
    public function getDefaultEmailAttribute()
    {
        return $this->emails()->where('is_default', true)->first()
            ?? $this->emails()->first();
    }

    /**
     * Get the contact's default phone number.
     */
    public function getDefaultPhoneAttribute()
    {
        return $this->phoneNumbers()->where('is_default', true)->first()
            ?? $this->phoneNumbers()->first();
    }
}
