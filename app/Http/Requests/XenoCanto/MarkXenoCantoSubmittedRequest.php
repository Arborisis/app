<?php

declare(strict_types=1);

namespace App\Http\Requests\XenoCanto;

use Illuminate\Foundation\Http\FormRequest;

class MarkXenoCantoSubmittedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'xeno_canto_id' => ['nullable', 'string', 'max:30', 'regex:/^[0-9]+$/'],
        ];
    }
}
