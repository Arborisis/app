<?php

declare(strict_types=1);

namespace App\Http\Requests\Report;

use App\Enums\ReportReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', Rule::enum(ReportReason::class)],
            'description' => ['nullable', 'string', 'max:2000'],
            'reportable_type' => ['required', 'string', Rule::in(['sound', 'comment'])],
            'reportable_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
