<?php

declare(strict_types=1);

namespace App\Http\Requests\AudioAnalysis;

use Illuminate\Foundation\Http\FormRequest;

class CallbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sound_id' => ['required', 'integer', 'exists:sounds,id'],
            'status' => ['required', 'string', 'in:completed,failed'],
            'force' => ['sometimes', 'boolean'],
            'results' => ['required_if:status,completed', 'array'],
            'results.original_r2_key' => ['sometimes', 'string', 'max:512'],
            'results.duration_seconds' => ['sometimes', 'numeric', 'min:0'],
            'results.sample_rate' => ['sometimes', 'integer', 'min:0'],
            'results.channels' => ['sometimes', 'integer', 'min:1', 'max:64'],
            'results.bitrate' => ['sometimes', 'integer', 'min:0'],
            'results.format' => ['sometimes', 'string', 'max:10'],
            'results.loudness_lufs' => ['sometimes', 'numeric'],
            'results.peak_db' => ['sometimes', 'numeric'],
            'results.rms_db' => ['sometimes', 'numeric'],
            'results.noise_floor_db' => ['sometimes', 'numeric'],
            'results.spectral_centroid' => ['sometimes', 'numeric', 'min:0'],
            'results.spectral_rolloff' => ['sometimes', 'numeric', 'min:0'],
            'results.zero_crossing_rate' => ['sometimes', 'numeric', 'min:0', 'max:1'],
            'results.waveform_r2_key' => ['sometimes', 'string', 'max:512'],
            'results.spectrogram_r2_key' => ['sometimes', 'string', 'max:512'],
            'results.features_r2_key' => ['sometimes', 'string', 'max:512'],
            'results.birdnet_r2_key' => ['sometimes', 'nullable', 'string', 'max:512'],
            'results.summary_r2_key' => ['sometimes', 'string', 'max:512'],
            'results.preview_r2_key' => ['sometimes', 'nullable', 'string', 'max:512'],
            'results.quality_label' => ['sometimes', 'string', 'in:excellent,good,medium,poor,unusable'],
            'results.quality_json' => ['sometimes', 'array'],
            'results.birdnet_detections' => ['sometimes', 'array'],
            'results.birdnet_detections.*.scientific_name' => ['required_with:results.birdnet_detections', 'string'],
            'results.birdnet_detections.*.common_name' => ['required_with:results.birdnet_detections', 'string'],
            'results.birdnet_detections.*.confidence' => ['required_with:results.birdnet_detections', 'numeric', 'between:0,1'],
            'results.birdnet_detections.*.start_time' => ['sometimes', 'numeric', 'min:0'],
            'results.birdnet_detections.*.end_time' => ['sometimes', 'numeric', 'min:0'],
            'error_message' => ['required_if:status,failed', 'nullable', 'string', 'max:5000'],
            'attempt' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
