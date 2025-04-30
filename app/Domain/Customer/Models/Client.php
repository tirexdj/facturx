<?php

namespace App\Domain\Customer\Models;

use App\Domain\Company\Models\Company;
use App\Domain\Invoice\Models\Invoice;
use App\Domain\Invoice\Models\InvoiceRecurrence;
use App\Domain\Invoice\Models\PaymentTerm;
use App\Domain\Quote\Models\Quote;
use App\Domain\Shared\Models\Address;
use App\Domain\Shared\Models\Contact;
use App\Domain\Shared\Models\Email;
use App\Domain\Shared\Models\PhoneNumber;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Client extends Model implements HasMedia
{
    use HasFactory, HasUuids, SoftDeletes, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'client_type',
        'name',
        'legal_name',
        'trading_name',
        'siren',
        'siret',
        'vat_number',
        'registration_number',
        'legal_form',
        'website',
        'category_id',
        'currency_code',
        'language_code',
        'payment_terms_id',
        'credit_limit',
        'notes',
        'tags',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'credit_limit' => 'decimal:2',
        'tags' => 'json',
    ];

    /**
     * Register media collections for the client.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile();
            
        $this->addMediaCollection('documents');
    }

    /**
     * Get the company that owns the client.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the category that the client belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the payment terms that the client uses.
     */
    public function paymentTerms(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class, 'payment_terms_id');
    }

    /**
     * Get all of the client's addresses.
     */
    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    /**
     * Get all of the client's phone numbers.
     */
    public function phoneNumbers(): MorphMany
    {
        return $this->morphMany(PhoneNumber::class, 'phoneable');
    }

    /**
     * Get all of the client's email addresses.
     */
    public function emails(): MorphMany
    {
        return $this->morphMany(Email::class, 'emailable');
    }

    /**
     * Get all of the contacts for the client.
     */
    public function contacts(): MorphMany
    {
        return $this->morphMany(Contact::class, 'contactable');
    }

    /**
     * Get all of the quotes for the client.
     */
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    /**
     * Get all of the invoices for the client.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get all of the invoice recurrences for the client.
     */
    public function invoiceRecurrences(): HasMany
    {
        return $this->hasMany(InvoiceRecurrence::class);
    }

    /**
     * Get the default billing address for the client.
     */
    public function getDefaultBillingAddressAttribute()
    {
        return $this->addresses()->where('is_billing', true)->where('is_default', true)->first()
            ?? $this->addresses()->where('is_billing', true)->first()
            ?? $this->addresses()->where('is_default', true)->first()
            ?? $this->addresses()->first();
    }

    /**
     * Get the default shipping address for the client.
     */
    public function getDefaultShippingAddressAttribute()
    {
        return $this->addresses()->where('is_shipping', true)->where('is_default', true)->first()
            ?? $this->addresses()->where('is_shipping', true)->first()
            ?? $this->addresses()->where('is_default', true)->first()
            ?? $this->addresses()->first();
    }

    /**
     * Get the primary contact for the client.
     */
    public function getPrimaryContactAttribute()
    {
        return $this->contacts()->where('is_primary', true)->first() 
            ?? $this->contacts()->first();
    }
    
    /**
     * Check if the client has overdue invoices.
     */
    public function hasOverdueInvoices(): bool
    {
        return $this->invoices()
            ->whereIn('status', ['sent', 'partial'])
            ->where('due_date', '<', now())
            ->exists();
    }
}
