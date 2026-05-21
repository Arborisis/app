<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MedalCategory;
use App\Enums\MedalRarity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medal extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'rarity',
        'category',
        'unlock_condition_type',
        'unlock_condition_value',
        'is_secret',
        'is_active',
    ];

    public function casts(): array
    {
        return [
            'rarity' => MedalRarity::class,
            'category' => MedalCategory::class,
            'unlock_condition_value' => 'array',
            'is_secret' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function userMedals(): HasMany
    {
        return $this->hasMany(UserMedal::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
