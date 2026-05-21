<?php

declare(strict_types=1);

namespace App\Http\Requests\Helpdesk;

use Illuminate\Foundation\Http\FormRequest;

class ValidateIaSuggestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:validate,reject,edit'],
            'edited_body' => ['required_if:action,edit', 'nullable', 'string', 'max:10000'],
            'rejection_reason' => ['required_if:action,reject', 'nullable', 'string', 'max:2000'],
        ];
    }
}
