<?php

declare(strict_types=1);

namespace App\Http\Requests\Scientific;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexListeningPointsRequest extends FormRequest
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
            'q' => ['sometimes', 'nullable', 'string', 'max:120'],
            'habitat' => [
                'sometimes',
                'nullable',
                'string',
                Rule::in(['forest', 'wetland', 'river', 'meadow', 'ocean', 'mountain', 'urban_nature', 'desert']),
            ],
            'sort' => ['sometimes', 'nullable', 'string', Rule::in(['recent', 'active', 'species', 'oldest', 'alpha'])],
        ];
    }

    /**
     * @return array{q: string, habitat: string, sort: string}
     */
    public function filters(): array
    {
        $validated = $this->validated();
        $sort = (string) ($validated['sort'] ?? 'recent');

        return [
            'q' => trim((string) ($validated['q'] ?? '')),
            'habitat' => (string) ($validated['habitat'] ?? ''),
            'sort' => $sort === '' ? 'recent' : $sort,
        ];
    }
}
