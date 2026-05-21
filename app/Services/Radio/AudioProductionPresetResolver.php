<?php

declare(strict_types=1);

namespace App\Services\Radio;

use App\Enums\RadioProductionPreset;
use App\Enums\RadioShowType;
use App\Models\RadioChannel;

class AudioProductionPresetResolver
{
    public function resolve(string|RadioShowType|null $showType = null, ?RadioChannel $channel = null, ?RadioProductionPreset $explicit = null): RadioProductionPresetConfig
    {
        $preset = $explicit
            ?? $channel?->production_preset
            ?? $this->presetForShowType($showType);

        $values = config("radio.presets.{$preset->value}", []);

        return RadioProductionPresetConfig::fromArray($preset, is_array($values) ? $values : []);
    }

    private function presetForShowType(string|RadioShowType|null $showType): RadioProductionPreset
    {
        if ($showType instanceof RadioShowType) {
            return RadioProductionPreset::forShowType($showType);
        }

        $enum = is_string($showType) ? RadioShowType::tryFrom($showType) : null;

        return $enum instanceof RadioShowType
            ? RadioProductionPreset::forShowType($enum)
            : RadioProductionPreset::PodcastIntimate;
    }
}
