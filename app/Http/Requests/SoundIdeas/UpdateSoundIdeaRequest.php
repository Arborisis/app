<?php

declare(strict_types=1);

namespace App\Http\Requests\SoundIdeas;

use App\Enums\SoundIdeaStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSoundIdeaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(SoundIdeaStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Le statut est requis.',
            'status.enum' => 'Le statut n\'est pas valide.',
        ];
    }
}
