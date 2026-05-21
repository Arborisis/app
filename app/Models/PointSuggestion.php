<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointSuggestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'arborisis_point_id',
        'field',
        'proposed_value',
        'reason',
        'status',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
    ];

    public function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
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

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
