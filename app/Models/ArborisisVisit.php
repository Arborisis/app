<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VisitStatus;
use App\Enums\VisitValidationReason;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArborisisVisit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'arborisis_point_id',
        'latitude',
        'longitude',
        'distance_from_point',
        'visited_at',
        'device_accuracy',
        'status',
        'validation_reason',
        'anti_cheat_score',
        'anti_cheat_notes',
    ];

    public function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'distance_from_point' => 'decimal:2',
            'device_accuracy' => 'decimal:2',
            'anti_cheat_score' => 'decimal:2',
            'visited_at' => 'datetime',
            'status' => VisitStatus::class,
            'validation_reason' => VisitValidationReason::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function arborisisPoint(): BelongsTo
    {
        return $this->belongsTo(ArborisisPoint::class);
    }

    public function scopeValid($query)
    {
        return $query->where('status', VisitStatus::Valid);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('visited_at', today());
    }

    public function isValid(): bool
    {
        return $this->status === VisitStatus::Valid;
    }
}
