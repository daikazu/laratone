<?php

declare(strict_types=1);

namespace Daikazu\Laratone\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SearchColorsRequest extends FormRequest
{
    private const int DEFAULT_LIMIT = 25;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'q'     => ['required', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:' . $this->maxLimit()],
        ];
    }

    public function searchQuery(): string
    {
        $query = $this->validated('q');

        return is_string($query) ? $query : '';
    }

    /**
     * Get the requested limit, defaulting to 25 (capped by max_match_limit).
     */
    public function limit(): int
    {
        $limit = $this->validated('limit');

        return is_numeric($limit) ? (int) $limit : min(self::DEFAULT_LIMIT, $this->maxLimit());
    }

    private function maxLimit(): int
    {
        $maxLimit = config('laratone.max_match_limit', 100);

        return is_numeric($maxLimit) ? (int) $maxLimit : 100;
    }
}
