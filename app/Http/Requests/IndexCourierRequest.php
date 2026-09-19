<?php

namespace App\Http\Requests;

use App\Models\Courier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexCourierRequest extends FormRequest
{
    private const DEFAULT_PER_PAGE = 15;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $levelRange = Courier::MIN_LEVEL.'-'.Courier::MAX_LEVEL;

        return [
            'search' => ['nullable', 'string', 'max:100'],
            // contoh valid: "2" atau "2,3"
            'level' => ['nullable', 'string', "regex:/^[{$levelRange}](,[{$levelRange}])*$/"],
            'sort' => ['nullable', Rule::in(Courier::SORTABLE_COLUMNS)],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }

    public function messages(): array
    {
        return [
            'level.regex' => 'The level must be a comma separated list of levels between '
                .Courier::MIN_LEVEL.' and '.Courier::MAX_LEVEL.' (e.g. 2,3).',
        ];
    }

    /**
     * @return array<int, int>
     */
    public function levels(): array
    {
        $level = $this->validated('level');

        if (! $level) {
            return [];
        }

        return array_values(array_unique(array_map('intval', explode(',', $level))));
    }

    public function sortColumn(): string
    {
        return $this->validated('sort') ?? 'name';
    }

    public function sortDirection(): string
    {
        return $this->validated('direction') ?? 'asc';
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? self::DEFAULT_PER_PAGE);
    }
}
