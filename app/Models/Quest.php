<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ObjectiveType;
use App\Enums\QuestType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quest extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'type',
        'category',
        'objective_type',
        'objective_target',
        'objective_payload',
        'reward_xp',
        'reward_medal_id',
        'reward_achievement_id',
        'starts_at',
        'ends_at',
        'is_repeatable',
        'is_active',
    ];

    public function casts(): array
    {
        return [
            'type' => QuestType::class,
            'objective_type' => ObjectiveType::class,
            'objective_payload' => 'array',
            'reward_xp' => 'integer',
            'objective_target' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_repeatable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function progress(): HasMany
    {
        return $this->hasMany(QuestProgress::class);
    }

    public function rewardMedal(): BelongsTo
    {
        return $this->belongsTo(Medal::class, 'reward_medal_id');
    }

    public function rewardAchievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class, 'reward_achievement_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }

    public function isAvailable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }
}
