<?php

declare(strict_types=1);

namespace App\Http\Requests\Agent;

use App\Enums\NatureSensitivityLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CreateItineraryRequest extends FormRequest
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
            'waypoints' => ['required', 'array', 'min:2', 'max:50'],
            'waypoints.*.title' => ['nullable', 'string', 'max:255'],
            'waypoints.*.place_query' => ['nullable', 'string', 'max:500'],
            'waypoints.*.description' => ['nullable', 'string', 'max:2000'],
            'waypoints.*.lat' => ['nullable', 'numeric', 'between:-90,90'],
            'waypoints.*.lng' => ['nullable', 'numeric', 'between:-180,180'],
            'waypoints.*.order' => ['required', 'integer', 'min:0'],
            'waypoints.*.arborisis_point_id' => ['nullable', 'integer', 'exists:arborisis_points,id'],
            'waypoints.*.recording_tips' => ['nullable', 'string', 'max:2000'],
            'waypoints.*.recommended_time' => ['nullable', 'string', 'max:100'],
            'start_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'start_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'nature_sensitivity_level' => ['nullable', 'string', Rule::enum(NatureSensitivityLevel::class)],
            'estimated_duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'difficulty_level' => ['nullable', 'integer', 'min:1', 'max:5'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'audio_environment_type' => ['nullable', 'string', 'max:100'],
            'cover_image' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ($this->input('waypoints', []) as $index => $waypoint) {
                if (! is_array($waypoint)) {
                    continue;
                }

                $hasPlaceQuery = trim((string) ($waypoint['place_query'] ?? '')) !== '';
                $hasCoordinates = isset($waypoint['lat'], $waypoint['lng'])
                    && is_numeric($waypoint['lat'])
                    && is_numeric($waypoint['lng']);
                $hasArborisisPoint = ! empty($waypoint['arborisis_point_id']);

                if (! $hasPlaceQuery && ! $hasCoordinates && ! $hasArborisisPoint) {
                    $validator->errors()->add(
                        "waypoints.{$index}.place_query",
                        'Chaque arrêt doit fournir un lieu à géocoder, des coordonnées fiables ou un point Arborisis.'
                    );
                }
            }
        });
    }
}
