<?php

declare(strict_types=1);

namespace App\Http\Requests\AudioAnalysis;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnalyzeSoundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'n_fft' => ['sometimes', 'integer', Rule::in([512, 1024, 2048, 4096])],
            'hop_length' => ['sometimes', 'integer', 'min:64', 'max:4096'],
            'frequency_scale' => ['sometimes', 'string', Rule::in(['linear', 'log', 'mel'])],
            'n_mels' => ['sometimes', 'integer', 'min:32', 'max:256'],
            'n_mfcc' => ['sometimes', 'integer', 'min:5', 'max:40'],
            'preprocessing' => ['sometimes', 'array'],
            'preprocessing.filter_type' => ['sometimes', 'nullable', 'string', Rule::in(['lowpass', 'highpass', 'bandpass'])],
            'preprocessing.cutoff' => ['sometimes', 'nullable', 'numeric', 'min:10'],
            'preprocessing.normalize' => ['sometimes', 'boolean'],
            'preprocessing.normalize_method' => ['sometimes', 'string', Rule::in(['peak', 'rms', 'zscore'])],
            'preprocessing.vad' => ['sometimes', 'boolean'],
            'visualization_types' => ['sometimes', 'array'],
            'visualization_types.*' => ['string', Rule::in(['stft', 'mel', 'mfcc', 'feature_correlation'])],
        ];
    }

    public function validatedConfig(): array
    {
        $data = $this->validated();

        return [
            'n_fft' => $data['n_fft'] ?? 2048,
            'hop_length' => $data['hop_length'] ?? 512,
            'frequency_scale' => $data['frequency_scale'] ?? 'linear',
            'n_mels' => $data['n_mels'] ?? 128,
            'n_mfcc' => $data['n_mfcc'] ?? 13,
            'preprocessing' => [
                'filter' => isset($data['preprocessing']['filter_type']) && $data['preprocessing']['filter_type']
                    ? ['type' => $data['preprocessing']['filter_type'], 'cutoff' => $data['preprocessing']['cutoff'] ?? 1000]
                    : null,
                'normalize' => $data['preprocessing']['normalize'] ?? true,
                'normalize_method' => $data['preprocessing']['normalize_method'] ?? 'peak',
                'vad' => $data['preprocessing']['vad'] ?? false,
            ],
            'visualizations' => [
                'spectrograms' => $data['visualization_types'] ?? ['stft', 'mel', 'mfcc'],
                'heatmaps' => ['feature_correlation'],
                'dpi' => 150,
                'colormap' => 'viridis',
            ],
        ];
    }
}
