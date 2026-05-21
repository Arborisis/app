<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Season;
use App\Enums\TimeOfDay;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvironmentalObservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'sound_id',
        'listening_point_id',
        'season',
        'time_of_day',
        'sun_altitude_deg',
        'temperature_c',
        'humidity_percent',
        'wind_speed_kmh',
        'wind_direction',
        'is_raining',
        'is_snowing',
        'weather_condition',
        'source',
        'raw_data',
    ];

    protected $casts = [
        'sun_altitude_deg' => 'decimal:2',
        'temperature_c' => 'decimal:1',
        'wind_speed_kmh' => 'decimal:1',
        'is_raining' => 'boolean',
        'is_snowing' => 'boolean',
        'raw_data' => 'array',
        'season' => Season::class,
        'time_of_day' => TimeOfDay::class,
    ];

    public function sound(): BelongsTo
    {
        return $this->belongsTo(Sound::class);
    }

    public function listeningPoint(): BelongsTo
    {
        return $this->belongsTo(ListeningPoint::class);
    }
}
