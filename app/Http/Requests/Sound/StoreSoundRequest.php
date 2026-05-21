<?php

declare(strict_types=1);

namespace App\Http\Requests\Sound;

use App\Enums\LicenseType;
use App\Enums\SoundVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSoundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role->canUpload();
    }

    public function rules(): array
    {
        return [
            'audio_file' => [
                'required',
                'file',
                'mimetypes:audio/mpeg,audio/wav,audio/x-wav,audio/wave,audio/vnd.wave,audio/flac,audio/x-flac,audio/mp4,audio/x-m4a,audio/m4a',
                'max:512000', // 500 MB
            ],
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'recorded_at' => ['nullable', 'date'],
            'recorded_time' => ['nullable', 'date_format:H:i'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'location_name' => ['nullable', 'string', 'max:255'],
            'is_sensitive_location' => ['boolean'],
            'tags' => ['nullable', 'string', 'max:500'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'new_category_name' => ['nullable', 'string', 'min:2', 'max:80'],
            'environment_id' => ['nullable', 'integer', 'exists:environments,id'],
            'new_environment_name' => ['nullable', 'string', 'min:2', 'max:80'],
            'equipment' => ['nullable', 'string', 'max:500'],
            'microphone_position' => ['nullable', 'string', 'max:500'],
            'weather_notes' => ['nullable', 'string', 'max:2000'],
            'perceived_activity_level' => ['nullable', 'integer', 'between:1,5'],
            'listening_point_id' => ['nullable', 'integer', 'exists:listening_points,id'],
            'create_new_listening_point' => ['boolean'],
            'listening_point_title' => ['nullable', 'string', 'max:255'],
            'license' => ['required', Rule::enum(LicenseType::class)],
            'visibility' => ['required', Rule::enum(SoundVisibility::class)],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'], // 10 MB
        ];
    }

    public function messages(): array
    {
        return [
            'audio_file.required' => 'Un fichier audio est requis.',
            'audio_file.mimetypes' => 'Le fichier doit être au format MP3, WAV, FLAC ou M4A.',
            'audio_file.max' => 'Le fichier ne doit pas dépasser 500 Mo.',
            'title.required' => 'Le titre est obligatoire.',
            'latitude.required' => 'La latitude est requise pour géolocaliser le son.',
            'longitude.required' => 'La longitude est requise pour géolocaliser le son.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_sensitive_location' => $this->boolean('is_sensitive_location'),
            'create_new_listening_point' => $this->boolean('create_new_listening_point'),
        ]);
    }
}
