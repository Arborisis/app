<?php

declare(strict_types=1);

namespace App\Http\Requests\Scientific;

use Illuminate\Foundation\Http\FormRequest;

class ScientificStatsRequest extends FormRequest
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
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date', 'after_or_equal:from'],
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'environment_id' => ['sometimes', 'integer', 'exists:environments,id'],
            'min_confidence' => ['sometimes', 'numeric', 'min:0', 'max:1'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'offset' => ['sometimes', 'integer', 'min:0', 'max:100000'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return collect($this->validated())
            ->only(['from', 'to', 'category_id', 'environment_id', 'min_confidence'])
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '')
            ->all();
    }

    public function resultLimit(int $default = 100, int $max = 1000): int
    {
        return min((int) $this->validated('limit', $default), $max);
    }

    public function resultOffset(): int
    {
        return (int) $this->validated('offset', 0);
    }
}
