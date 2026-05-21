<?php

declare(strict_types=1);

namespace App\Http\Requests\Gamification;

use App\Enums\PointReportReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportPointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', Rule::enum(PointReportReason::class)],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Le motif du signalement est requis.',
        ];
    }
}
