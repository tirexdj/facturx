<?php

namespace App\Domain\Analytics\Models;

use App\Domain\Company\Models\Plan;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanFeature extends Pivot
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'plan_features';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'plan_id',
        'feature_id',
        'is_enabled',
        'value_limit',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_enabled' => 'boolean',
        'value_limit' => 'integer',
    ];

    /**
     * Get the plan that owns the plan feature.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the feature that owns the plan feature.
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }

    /**
     * Check if the feature is unlimited.
     */
    public function isUnlimited(): bool
    {
        return $this->value_limit === -1;
    }

    /**
     * Get the usage limit for this feature.
     */
    public function getUsageLimit(): ?int
    {
        return $this->isUnlimited() ? null : $this->value_limit;
    }
}
