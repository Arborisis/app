<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SoundLocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sound_id',
        'exact_latitude',
        'exact_longitude',
        'public_latitude',
        'public_longitude',
        'location_name',
        'is_sensitive',
    ];

    protected $hidden = [
        'exact_latitude',
        'exact_longitude',
    ];

    protected $casts = [
        'exact_latitude' => 'decimal:8',
        'exact_longitude' => 'decimal:8',
        'public_latitude' => 'decimal:8',
        'public_longitude' => 'decimal:8',
        'is_sensitive' => 'boolean',
    ];

    public function sound(): BelongsTo
    {
        return $this->belongsTo(Sound::class);
    }

    public function listeningPoint(): BelongsTo
    {
        return $this->belongsTo(ListeningPoint::class);
    }

    /**
     * Obscure exact coordinates by rounding them for public display.
     */
    public static function obscure(float $latitude, float $longitude): array
    {
        return [
            'public_latitude' => round($latitude, 2),
            'public_longitude' => round($longitude, 2),
        ];
    }
}
