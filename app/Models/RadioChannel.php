<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RadioProductionPreset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RadioChannel extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'slug',
        'name',
        'mount_path',
        'color',
        'description',
        'vibe',
        'playlist_source',
        'production_preset',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'vibe' => 'array',
        'playlist_source' => 'array',
        'production_preset' => RadioProductionPreset::class,
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function listenerSessions(): HasMany
    {
        return $this->hasMany(RadioListenerSession::class);
    }

    public function podcasts(): HasMany
    {
        return $this->hasMany(RadioPodcast::class);
    }

    public static function main(): ?self
    {
        return static::query()->where('slug', 'main')->first();
    }
}
