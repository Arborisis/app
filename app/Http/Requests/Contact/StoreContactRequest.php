<?php

declare(strict_types=1);

namespace App\Http\Requests\Contact;

use App\Enums\ContactTicketType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(ContactTicketType::class)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Veuillez sélectionner un type de demande.',
            'name.required' => 'Veuillez indiquer votre nom.',
            'email.required' => 'Veuillez indiquer votre adresse e-mail.',
            'email.email' => 'L\'adresse e-mail n\'est pas valide.',
            'subject.required' => 'Veuillez indiquer un sujet.',
            'message.required' => 'Veuillez rédiger votre message.',
        ];
    }
}
