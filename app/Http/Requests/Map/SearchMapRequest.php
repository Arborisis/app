<?php

declare(strict_types=1);

namespace App\Http\Requests\Map;

use Illuminate\Foundation\Http\FormRequest;

class SearchMapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ];
    }
}
