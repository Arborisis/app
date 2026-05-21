<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QuestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestProgress extends Model
{
    use HasFactory;

    protected $table = 'quest_progress';

    protected $fillable = [
        'user_id',
        'quest_id',
        'current_progress',
        'target_progress',
        'status',
        'started_at',
        'completed_at',
        'claimed_at',
    ];

    public function casts(): array
    {
        return [
            'current_progress' => 'integer',
            'target_progress' => 'integer',
            'status' => QuestStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'claimed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quest(): BelongsTo
    {
        return $this->belongsTo(Quest::class);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', QuestStatus::InProgress);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', QuestStatus::Completed);
    }

    public function isCompleted(): bool
    {
        return $this->status === QuestStatus::Completed || $this->status === QuestStatus::Claimed;
    }

    public function progressPercentage(): int
    {
        if ($this->target_progress <= 0) {
            return 0;
        }

        return min(100, (int) round(($this->current_progress / $this->target_progress) * 100));
    }
}
