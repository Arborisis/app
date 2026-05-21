<?php

declare(strict_types=1);

namespace App\Services\Radio\Filters;

use App\Services\Radio\RadioProductionPresetConfig;

class VoiceChainFilter
{
    /**
     * @param  list<string>  $outputLabels
     */
    public function build(int $inputIndex, RadioProductionPresetConfig $preset, array $outputLabels): string
    {
        $chain = "[{$inputIndex}:a]aformat=sample_rates=44100:channel_layouts=stereo"
            . ',volume=1.0'
            . ',highpass=f='.$preset->voiceHighpassHz
            . ',acompressor=threshold=-18dB:ratio=2.5:attack=8:release=160'
            . ',deesser=i=0.25:m=0.5:f=0.5';

        if (count($outputLabels) > 1) {
            return $chain.',asplit='.count($outputLabels).implode('', $outputLabels);
        }

        return $chain.$outputLabels[0];
    }
}
