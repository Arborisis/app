<?php

declare(strict_types=1);

namespace App\Http\Requests\Helpdesk;

use App\Enums\ContactTicketCategory;
use App\Enums\ContactTicketType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHelpdeskTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::enum(ContactTicketType::class)],
            'category' => ['required', 'string', Rule::enum(ContactTicketCategory::class)],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'type de demande',
            'category' => 'catégorie',
            'subject' => 'sujet',
            'message' => 'message',
        ];
    }
}
