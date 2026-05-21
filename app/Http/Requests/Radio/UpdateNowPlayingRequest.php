<?php

declare(strict_types=1);

namespace App\Http\Requests\Radio;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNowPlayingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sound_id' => ['nullable', 'integer', 'exists:sounds,id'],
            'title' => ['required', 'string', 'max:255'],
            'artist' => ['nullable', 'string', 'max:255'],
            'cover' => ['nullable', 'string', 'max:2048'],
            'duration' => ['nullable', 'integer', 'min:0'],
            'started_at' => ['nullable', 'date'],
            'slug' => ['nullable', 'string', 'max:255'],
            'kind' => ['nullable', 'string', 'in:sound,jingle,dj,silence'],
            'next_up' => ['nullable', 'array'],
            'next_up.sound_id' => ['nullable', 'integer'],
            'next_up.title' => ['nullable', 'string', 'max:255'],
            'next_up.artist' => ['nullable', 'string', 'max:255'],
            'listeners' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
