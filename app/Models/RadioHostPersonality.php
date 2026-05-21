<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RadioDaypart;
use App\Enums\RadioShowType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RadioHostPersonality extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'slug',
        'display_name',
        'voice_provider',
        'voice_id',
        'voice_settings',
        'prose_brief',
        'forbidden_phrases',
        'preferred_lexicon',
        'dayparts',
        'show_types',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'voice_settings' => 'array',
        'forbidden_phrases' => 'array',
        'preferred_lexicon' => 'array',
        'dayparts' => 'array',
        'show_types' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function supportsDaypart(RadioDaypart $daypart): bool
    {
        $dayparts = $this->dayparts ?? [];

        return $dayparts === [] || in_array($daypart->value, $dayparts, true);
    }

    public function supportsShowType(string $showType): bool
    {
        $showTypes = $this->show_types ?? [];

        return $showTypes === [] || in_array($showType, $showTypes, true);
    }

    public function supportsRadioShowType(RadioShowType $showType): bool
    {
        return $this->supportsShowType($showType->value);
    }

    public function voiceSettingsArray(): array
    {
        $settings = $this->voice_settings ?? [];

        return [
            'stability' => (float) ($settings['stability'] ?? 0.55),
            'similarity_boost' => (float) ($settings['similarity_boost'] ?? 0.75),
            'style' => (float) ($settings['style'] ?? 0.20),
            'use_speaker_boost' => (bool) ($settings['use_speaker_boost'] ?? true),
        ];
    }
}
