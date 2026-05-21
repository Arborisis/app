<?php

declare(strict_types=1);

namespace App\Http\Requests\Comment;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:1', 'max:2000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ];
    }
}
