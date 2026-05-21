<?php

declare(strict_types=1);

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1', 'max:500'],
        ];
    }
}
