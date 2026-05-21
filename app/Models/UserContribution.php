<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserContribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'listening_point_id',
        'recordings_count',
        'species_found_count',
        'total_duration_seconds',
        'first_contribution_at',
        'last_contribution_at',
    ];

    protected $casts = [
        'recordings_count' => 'integer',
        'species_found_count' => 'integer',
        'total_duration_seconds' => 'decimal:2',
        'first_contribution_at' => 'datetime',
        'last_contribution_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function listeningPoint(): BelongsTo
    {
        return $this->belongsTo(ListeningPoint::class);
    }
}
