<?php

declare(strict_types=1);

namespace App\Http\Requests\Gamification;

use App\Enums\ArborisisCategory;
use App\Enums\NatureSensitivityLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArborisisPointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->email_verified_at !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'category' => ['required', Rule::enum(ArborisisCategory::class)],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'difficulty_level' => ['nullable', 'integer', 'between:1,5'],
            'nature_sensitivity_level' => ['nullable', Rule::enum(NatureSensitivityLevel::class)],
            'recommended_time' => ['nullable', 'string', 'max:100'],
            'audio_environment_type' => ['nullable', 'string', 'max:100'],
            'cover_image' => ['nullable', 'image', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Le titre du point est requis.',
            'title.min' => 'Le titre doit faire au moins 3 caractères.',
            'latitude.required' => 'La latitude est requise.',
            'longitude.required' => 'La longitude est requise.',
            'category.required' => 'La catégorie est requise.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'difficulty_level' => $this->input('difficulty_level', 1),
            'nature_sensitivity_level' => $this->input('nature_sensitivity_level', NatureSensitivityLevel::Normal->value),
        ]);
    }
}
