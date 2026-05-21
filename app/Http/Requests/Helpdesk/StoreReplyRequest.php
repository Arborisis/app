<?php

declare(strict_types=1);

namespace App\Http\Requests\Helpdesk;

use Illuminate\Foundation\Http\FormRequest;

class StoreReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:10000'],
            'is_internal_note' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'Le message est obligatoire.',
        ];
    }
}
