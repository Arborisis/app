<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SoundWalkPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'sound_walk_id',
        'arborisis_point_id',
        'title',
        'description',
        'latitude',
        'longitude',
        'order',
        'stop_metadata',
    ];

    protected $hidden = [
        'latitude',
        'longitude',
    ];

    public function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'order' => 'integer',
            'stop_metadata' => 'array',
        ];
    }

    public function soundWalk(): BelongsTo
    {
        return $this->belongsTo(SoundWalk::class);
    }

    public function arborisisPoint(): BelongsTo
    {
        return $this->belongsTo(ArborisisPoint::class);
    }
}
