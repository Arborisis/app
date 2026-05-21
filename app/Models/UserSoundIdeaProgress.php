<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SoundIdeaStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSoundIdeaProgress extends Model
{
    use HasFactory;

    protected $table = 'user_sound_idea_progress';

    protected $fillable = [
        'user_id',
        'daily_sound_idea_id',
        'status',
        'completed_at',
        'dismissed_at',
    ];

    public function casts(): array
    {
        return [
            'status' => SoundIdeaStatus::class,
            'completed_at' => 'datetime',
            'dismissed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dailySoundIdea(): BelongsTo
    {
        return $this->belongsTo(DailySoundIdea::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', SoundIdeaStatus::Pending);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', SoundIdeaStatus::Completed);
    }

    public function isCompleted(): bool
    {
        return $this->status === SoundIdeaStatus::Completed;
    }
}
