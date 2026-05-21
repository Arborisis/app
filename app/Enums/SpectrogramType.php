<?php

declare(strict_types=1);

namespace App\Enums;

enum SpectrogramType: string
{
    case STFT = 'stft';
    case MEL_SPECTROGRAM = 'mel_spectrogram';
    case MFCC = 'mfcc';
    case FEATURE_CORRELATION = 'feature_correlation';
    case CONFUSION_MATRIX = 'confusion_matrix';
}
