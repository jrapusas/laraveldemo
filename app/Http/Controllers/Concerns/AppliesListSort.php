<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

trait AppliesListSort
{
    /**
     * @param  array<string, string>  $sortMap  API key => SQL column/expression
     * @return array<string, array<int, mixed>>
     */
    protected function listSortValidationRules(array $sortMap): array
    {
        return [
            'sort' => ['sometimes', 'string', Rule::in(array_keys($sortMap))],
            'direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
        ];
    }

    /**
     * @param  Builder  $query
     * @param  array<string, mixed>  $filters
     * @param  array<string, string>  $sortMap
     */
    protected function applyListSort($query, array $filters, array $sortMap): void
    {
        $sort = $filters['sort'] ?? 'created_at';
        if (! array_key_exists($sort, $sortMap)) {
            $sort = array_key_first($sortMap) ?? 'created_at';
        }
        $direction = $filters['direction'] ?? 'desc';
        if ($direction !== 'asc') {
            $direction = 'desc';
        }

        $query->orderBy($sortMap[$sort], $direction);
    }

    protected function likeContains(string $term): string
    {
        return '%'.addcslashes($term, '%_\\').'%';
    }
}
