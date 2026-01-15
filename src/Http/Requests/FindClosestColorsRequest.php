<?php

declare(strict_types=1);

namespace Daikazu\Laratone\Http\Requests;

use Daikazu\Laratone\Services\ColorMatcher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class FindClosestColorsRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     * Normalizes hex input by removing # prefix and converting to uppercase.
     */
    protected function prepareForValidation(): void
    {
        $hex = $this->input('hex');

        if ($hex !== null && is_string($hex)) {
            // Remove # prefix if present and convert to uppercase
            $this->merge([
                'hex' => strtoupper(ltrim($hex, '#')),
            ]);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $maxLimit = config('laratone.max_match_limit', 100);
        $maxLimit = is_numeric($maxLimit) ? (int) $maxLimit : 100;

        return [
            'hex'       => ['required', 'string', 'regex:/^[0-9A-F]{6}$/'],
            'limit'     => ['nullable', 'integer', 'min:1', 'max:' . $maxLimit],
            'algorithm' => ['nullable', Rule::in(ColorMatcher::availableAlgorithms())],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'hex.required' => 'A hex color value is required.',
            'hex.regex'    => 'The hex color must be a valid 6-character hex code (e.g., FF5500 or #FF5500).',
            'algorithm.in' => 'The algorithm must be one of: ' . implode(', ', ColorMatcher::availableAlgorithms()) . '.',
        ];
    }

    /**
     * Get the normalized hex color (uppercase, without #).
     */
    public function hex(): string
    {
        $hex = $this->validated('hex');

        return is_string($hex) ? $hex : '';
    }

    /**
     * Get the requested limit (number of results).
     * Returns 1 if not specified.
     */
    public function limit(): int
    {
        $limit = $this->validated('limit');

        return is_numeric($limit) ? (int) $limit : 1;
    }

    /**
     * Get the requested algorithm.
     * Returns the configured default if not specified.
     */
    public function algorithm(): string
    {
        $algorithm = $this->validated('algorithm');

        if (is_string($algorithm)) {
            return $algorithm;
        }

        $default = config('laratone.default_match_algorithm', ColorMatcher::ALGORITHM_LAB);

        return is_string($default) ? $default : ColorMatcher::ALGORITHM_LAB;
    }
}
