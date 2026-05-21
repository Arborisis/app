<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RadioDaypart;
use App\Enums\RadioHostPersonality as RadioHostPersonalityEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadioDjAnnouncement extends Model
{
    use HasFactory;

    protected $fillable = [
        'sound_id',
        'previous_sound_id',
        'next_sound_id',
        'voice_provider',
        'voice_id',
        'personality_slug',
        'text',
        'disk',
        'path',
        'mime_type',
        'duration',
        'text_hash',
        'prompt_hash',
        'phrase_fingerprint',
        'daypart',
        'generated_at',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'duration' => 'integer',
    ];

    public function sound(): BelongsTo
    {
        return $this->belongsTo(Sound::class);
    }

    public function previousSound(): BelongsTo
    {
        return $this->belongsTo(Sound::class, 'previous_sound_id');
    }

    public function nextSound(): BelongsTo
    {
        return $this->belongsTo(Sound::class, 'next_sound_id');
    }

    public function personality(): ?RadioHostPersonalityEnum
    {
        if ($this->personality_slug === null) {
            return null;
        }

        return RadioHostPersonalityEnum::tryFrom($this->personality_slug);
    }

    public function daypartEnum(): ?RadioDaypart
    {
        if ($this->daypart === null) {
            return null;
        }

        return RadioDaypart::tryFrom($this->daypart);
    }
}
