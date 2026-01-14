<?php

declare(strict_types=1);

namespace Daikazu\Laratone\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ColorBookRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'limit'  => ['nullable', 'integer', 'min:1'],
            'sort'   => ['nullable', Rule::in(['asc', 'desc'])],
            'random' => ['nullable', 'boolean'],
        ];
    }

    public function limit(): ?int
    {
        $limit = $this->validated('limit');

        return $limit !== null ? (int) $limit : null;
    }

    public function sortDirection(): ?string
    {
        return $this->validated('sort');
    }

    public function isRandom(): bool
    {
        return (bool) $this->validated('random', false);
    }
}
