<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AchievementCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Achievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'icon',
        'category',
        'points',
        'condition_type',
        'condition_payload',
        'is_hidden',
        'is_active',
    ];

    public function casts(): array
    {
        return [
            'category' => AchievementCategory::class,
            'points' => 'integer',
            'condition_payload' => 'array',
            'is_hidden' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function userAchievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
