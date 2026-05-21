<?php

declare(strict_types=1);

namespace App\Http\Requests\Contact;

use Illuminate\Foundation\Http\FormRequest;

class TrackContactTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ticket' => ['nullable', 'string', 'regex:/^ARB-\d{8}-[A-Z0-9]{5}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'ticket.regex' => 'Le numéro de suivi doit être au format ARB-YYYYMMDD-XXXXX.',
        ];
    }
}
