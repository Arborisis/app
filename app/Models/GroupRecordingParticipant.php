<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupRecordingParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'status',
    ];

    public function casts(): array
    {
        return [
            'joined_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(GroupRecordingEvent::class, 'event_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
