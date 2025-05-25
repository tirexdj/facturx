<?php

namespace App\Domain\Company\Models;

use App\Domain\Analytics\Models\Feature;
use App\Domain\Analytics\Models\FeatureUsage;
use App\Domain\Analytics\Models\PlanFeature;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Plan extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\PlanFactory> */
    use HasFactory, HasUuids, SoftDeletes, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'price_monthly',
        'price_yearly',
        'currency_code',
        'is_active',
        'is_public',
        'trial_days',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'trial_days' => 'integer',
    ];

    /**
     * Register media collections for the plan.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('plan_images');
    }

    /**
     * Get the companies that belong to the plan.
     */
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    /**
     * Get the features that belong to the plan.
     */
    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 'plan_features')
            ->withPivot(['is_enabled', 'value_limit'])
            ->withTimestamps();
    }

    /**
     * Get the plan features for the plan.
     */
    public function planFeatures(): HasMany
    {
        return $this->hasMany(PlanFeature::class);
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Database\Factories\PlanFactory::new();
    }
}
