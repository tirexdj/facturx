<?php

namespace App\Domain\Analytics\Models;

use App\Domain\Company\Models\Company;
use App\Domain\Company\Models\Plan;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feature extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'category',
    ];

    /**
     * Get the plans that have this feature.
     */
    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'plan_features')
            ->using(PlanFeature::class)
            ->withPivot(['is_enabled', 'value_limit'])
            ->withTimestamps();
    }

    /**
     * Get the plan features for this feature.
     */
    public function planFeatures(): HasMany
    {
        return $this->hasMany(PlanFeature::class);
    }

    /**
     * Get the feature usage records for this feature.
     */
    public function featureUsage(): HasMany
    {
        return $this->hasMany(FeatureUsage::class);
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Database\Factories\Domain\Analytics\Models\FeatureFactory::new();
    }
}
