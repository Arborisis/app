<?php

declare(strict_types=1);

namespace App\Http\Requests\Agent;

use Illuminate\Foundation\Http\FormRequest;

class ChatAgentRequest extends FormRequest
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
            'message' => ['required', 'string', 'min:2', 'max:4000'],
            'conversation_id' => ['nullable', 'string', 'max:80'],
            'history' => ['nullable', 'array', 'max:12'],
            'history.*.role' => ['required_with:history', 'string', 'in:user,assistant'],
            'history.*.content' => ['required_with:history', 'string', 'max:4000'],
            'page' => ['nullable', 'array'],
            'page.url' => ['nullable', 'string', 'max:500'],
            'page.title' => ['nullable', 'string', 'max:200'],
            'location' => ['nullable', 'array'],
            'location.lat' => ['required_with:location', 'numeric', 'between:-90,90'],
            'location.lng' => ['required_with:location', 'numeric', 'between:-180,180'],
            'location.accuracy' => ['nullable', 'numeric', 'min:0', 'max:50000'],
        ];
    }
}
