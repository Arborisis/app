<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailySoundIdea extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'theme',
        'title',
        'description',
        'difficulty',
        'tags',
        'season_context',
        'weather_context',
        'time_of_day',
    ];

    public function casts(): array
    {
        return [
            'date' => 'date',
            'tags' => 'array',
        ];
    }

    public function userProgress(): HasMany
    {
        return $this->hasMany(UserSoundIdeaProgress::class);
    }

    public function scopeForDate($query, \Carbon\Carbon $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('date', today());
    }
}
