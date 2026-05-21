<?php

declare(strict_types=1);

namespace App\Http\Requests\Gamification;

use App\Enums\ArborisisCategory;
use App\Enums\NatureSensitivityLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateArborisisPointRequest extends FormRequest
{
    public function authorize(): bool
    {
        $point = $this->route('arborisis_point');

        return auth()->check()
            && auth()->user()->can('update', $point);
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['sometimes', Rule::enum(ArborisisCategory::class)],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'difficulty_level' => ['nullable', 'integer', 'between:1,5'],
            'nature_sensitivity_level' => ['nullable', Rule::enum(NatureSensitivityLevel::class)],
            'recommended_time' => ['nullable', 'string', 'max:100'],
            'audio_environment_type' => ['nullable', 'string', 'max:100'],
            'cover_image' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
