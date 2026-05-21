<?php

declare(strict_types=1);

namespace App\Services\Radio\Filters;

use App\Services\Radio\RadioProductionPresetConfig;

class SidechainBedFilter
{
    public function build(string $rawLabel, string $voiceLabel, string $outputLabel, RadioProductionPresetConfig $preset): string
    {
        return sprintf(
            '%s%s%s',
            $rawLabel,
            $voiceLabel,
            'sidechaincompress=threshold=0.025:ratio='.$this->floatForFfmpeg($preset->sidechainRatio)
                .':attack='.$preset->sidechainAttack
                .':release='.$preset->sidechainRelease
                .':makeup=1'
                .$outputLabel,
        );
    }

    private function floatForFfmpeg(float $value): string
    {
        return rtrim(rtrim(sprintf('%.2F', $value), '0'), '.');
    }
}
