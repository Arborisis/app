<?php

declare(strict_types=1);

namespace App\Http\Requests\Agent;

use App\Enums\ArborisisCategory;
use App\Enums\NatureSensitivityLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->email_verified_at !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:2', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'category' => ['required', 'string', Rule::enum(ArborisisCategory::class)],
            'nature_sensitivity_level' => ['nullable', 'string', Rule::enum(NatureSensitivityLevel::class)],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'recommended_time' => ['nullable', 'string', 'max:100'],
            'audio_environment_type' => ['nullable', 'string', 'max:100'],
            'cover_image' => ['nullable', 'string', 'max:500'],
        ];
    }
}
