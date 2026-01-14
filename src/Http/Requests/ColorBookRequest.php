<?php

declare(strict_types=1);

namespace Daikazu\Laratone\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ColorBookRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     * Converts string 'true'/'false' to actual booleans for the random parameter.
     * Only converts known boolean string values, leaving invalid strings unchanged.
     */
    protected function prepareForValidation(): void
    {
        $random = $this->input('random');

        if ($random !== null && is_string($random)) {
            // Only convert recognized boolean string representations
            $booleanStrings = ['true', 'false', 'yes', 'no', 'on', 'off', '1', '0'];

            if (in_array(strtolower($random), $booleanStrings, true)) {
                $this->merge([
                    'random' => filter_var($random, FILTER_VALIDATE_BOOLEAN),
                ]);
            }
            // Leave invalid strings unchanged so validation can reject them
        }
    }

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
