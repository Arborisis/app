<?php

declare(strict_types=1);

namespace App\Http\Requests\EchoDonation;

use App\Enums\EchoDonationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEchoDonationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipient_id' => ['required', 'integer', 'exists:users,id', 'different:user_id'],
            'sound_id' => ['nullable', 'integer', 'exists:sounds,id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:10000'],
            'type' => ['required', 'string', Rule::enum(EchoDonationType::class)],
            'message' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'recipient_id.different' => 'Vous ne pouvez pas vous donner des ECHO à vous-même.',
            'amount.min' => 'Le montant minimum est de 0.01 ECHO.',
            'amount.max' => 'Le montant maximum est de 10 000 ECHO.',
        ];
    }
}
