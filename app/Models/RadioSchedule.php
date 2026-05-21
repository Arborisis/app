<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RadioScheduleRepeat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class RadioSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'starts_at',
        'ends_at',
        'repeat',
        'priority',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'repeat' => RadioScheduleRepeat::class,
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    public function sounds(): BelongsToMany
    {
        return $this->belongsToMany(Sound::class, 'radio_schedule_sound')
            ->withPivot('position')
            ->withTimestamps()
            ->orderByPivot('position');
    }

    public function isCurrentlyActive(?Carbon $now = null): bool
    {
        $now ??= now();

        if (! $this->is_active || ! $this->starts_at) {
            return $this->is_active && (! $this->ends_at || $this->ends_at->greaterThanOrEqualTo($now));
        }

        if ($this->repeat === RadioScheduleRepeat::None) {
            return $this->starts_at->lessThanOrEqualTo($now)
                && (! $this->ends_at || $this->ends_at->greaterThanOrEqualTo($now));
        }

        if ($this->ends_at && $this->ends_at->lessThan($this->starts_at)) {
            return false;
        }

        $sameRepeatWindow = match ($this->repeat) {
            RadioScheduleRepeat::Daily => true,
            RadioScheduleRepeat::Weekly => $this->starts_at->dayOfWeek === $now->dayOfWeek,
            RadioScheduleRepeat::Monthly => $this->starts_at->day === $now->day,
            RadioScheduleRepeat::None => false,
        };

        if (! $sameRepeatWindow) {
            return false;
        }

        $windowStart = $now->copy()->setTimeFrom($this->starts_at);
        $windowEnd = $this->ends_at
            ? $now->copy()->setTimeFrom($this->ends_at)
            : $windowStart->copy()->endOfDay();

        return $now->betweenIncluded($windowStart, $windowEnd);
    }
}
