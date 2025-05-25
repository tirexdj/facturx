<?php

namespace App\Domain\Company\Models;

use App\Domain\Auth\Models\User;
use App\Domain\Customer\Models\Client;
use App\Domain\Customer\Models\Category;
use App\Domain\EReporting\Models\EReportingTransmission;
use App\Domain\EReporting\Models\PdpConfiguration;
use App\Domain\Invoice\Models\Invoice;
use App\Domain\Invoice\Models\PaymentMethod;
use App\Domain\Invoice\Models\PaymentTerm;
use App\Domain\Invoice\Models\Template;
use App\Domain\Payment\Models\Payment;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductAttribute;
use App\Domain\Product\Models\Service;
use App\Domain\Product\Models\Unit;
use App\Domain\Product\Models\VatRate;
use App\Domain\Quote\Models\Quote;
use App\Domain\Shared\Models\Address;
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

class Company extends Model implements HasMedia
{
    use HasFactory, HasUuids, SoftDeletes, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'legal_name',
        'trading_name',
        'siren',
        'siret',
        'vat_number',
        'registration_number',
        'legal_form',
        'website',
        'logo_path',
        'plan_id',
        'pdp_id',
        'vat_regime',
        'fiscal_year_start',
        'currency_code',
        'language_code',
        'created_by',
        'updated_by',
    ];

    /**
     * Register media collections for the company.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile();
            
        $this->addMediaCollection('documents');
    }

    /**
     * Get the plan that the company belongs to.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the users that belong to the company.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all of the company's addresses.
     */
    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    /**
     * Get all of the company's phone numbers.
     */
    public function phoneNumbers(): MorphMany
    {
        return $this->morphMany(PhoneNumber::class, 'phoneable');
    }

    /**
     * Get all of the company's email addresses.
     */
    public function emails(): MorphMany
    {
        return $this->morphMany(Email::class, 'emailable');
    }

    /**
     * Get all of the clients for the company.
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    /**
     * Get all of the products for the company.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get all of the services for the company.
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Get all of the categories for the company.
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Get all of the units for the company.
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    /**
     * Get all of the VAT rates for the company.
     */
    public function vatRates(): HasMany
    {
        return $this->hasMany(VatRate::class);
    }

    /**
     * Get all of the product attributes for the company.
     */
    public function productAttributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class);
    }

    /**
     * Get all of the payment terms for the company.
     */
    public function paymentTerms(): HasMany
    {
        return $this->hasMany(PaymentTerm::class);
    }

    /**
     * Get all of the payment methods for the company.
     */
    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    /**
     * Get all of the templates for the company.
     */
    public function templates(): HasMany
    {
        return $this->hasMany(Template::class);
    }

    /**
     * Get all of the quotes for the company.
     */
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    /**
     * Get all of the invoices for the company.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get all of the payments for the company.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get all of the PDP configurations for the company.
     */
    public function pdpConfigurations(): HasMany
    {
        return $this->hasMany(PdpConfiguration::class);
    }

    /**
     * Get all of the e-reporting transmissions for the company.
     */
    public function eReportingTransmissions(): HasMany
    {
        return $this->hasMany(EReportingTransmission::class);
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Database\Factories\CompanyFactory::new();
    }
}
