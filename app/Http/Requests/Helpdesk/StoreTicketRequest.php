<?php

declare(strict_types=1);

namespace App\Http\Requests\Helpdesk;

use App\Enums\HelpdeskTicketPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'integer', 'exists:helpdesk_categories,id'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'priority' => ['required', 'string', Rule::in(array_map(fn ($c) => $c->value, HelpdeskTicketPriority::cases()))],
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required' => 'Le sujet du ticket est obligatoire.',
            'body.required' => 'Veuillez décrire votre problème.',
            'priority.required' => 'La priorité est obligatoire.',
        ];
    }
}
