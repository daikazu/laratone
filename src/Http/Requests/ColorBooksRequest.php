<?php

declare(strict_types=1);

namespace Daikazu\Laratone\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ColorBooksRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'sort' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }

    /**
     * @return 'asc'|'desc'|null
     */
    public function sortDirection(): ?string
    {
        $sort = $this->validated('sort');

        return in_array($sort, ['asc', 'desc'], true) ? $sort : null;
    }
}
