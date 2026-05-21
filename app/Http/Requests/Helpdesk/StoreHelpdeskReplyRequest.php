<?php

declare(strict_types=1);

namespace App\Http\Requests\Helpdesk;

use Illuminate\Foundation\Http\FormRequest;

class StoreHelpdeskReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'message' => 'message',
        ];
    }
}
