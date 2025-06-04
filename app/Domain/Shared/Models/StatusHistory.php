<?php

namespace App\Domain\Shared\Models;

use App\Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StatusHistory extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'model_type',
        'model_id',
        'old_status',
        'new_status',
        'reason',
        'user_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'json',
    ];

    /**
     * Get the parent model.
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who made the status change.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by model type.
     */
    public function scopeForModelType($query, string $modelType)
    {
        return $query->where('model_type', $modelType);
    }

    /**
     * Scope to filter by status change.
     */
    public function scopeStatusChange($query, string $from, string $to)
    {
        return $query->where('old_status', $from)->where('new_status', $to);
    }

    /**
     * Get formatted status change description.
     */
    public function getDescriptionAttribute(): string
    {
        $oldLabel = $this->getStatusLabel($this->old_status);
        $newLabel = $this->getStatusLabel($this->new_status);
        
        return "Changement de statut : {$oldLabel} → {$newLabel}";
    }

    /**
     * Get status label from enum if possible.
     */
    protected function getStatusLabel(string $status): string
    {
        // Try to get label from enum based on model type
        if ($this->model_type === 'App\Domain\Quote\Models\Quote') {
            try {
                return \App\Domain\Shared\Enums\QuoteStatus::from($status)->label();
            } catch (\ValueError $e) {
                return ucfirst($status);
            }
        }

        return ucfirst($status);
    }
}
