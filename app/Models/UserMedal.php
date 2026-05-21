<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMedal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'medal_id',
        'unlocked_at',
        'source_type',
        'source_id',
    ];

    public function casts(): array
    {
        return [
            'unlocked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function medal(): BelongsTo
    {
        return $this->belongsTo(Medal::class);
    }
}
