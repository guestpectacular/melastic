<?php

namespace Guestpectacular\Melastic\Engines;

use InvalidArgumentException;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\MeilisearchEngine as ScoutMeilisearchEngine;

class MeilisearchEngine extends ScoutMeilisearchEngine
{
    /**
     * Perform the given search on the engine.
     *
     * @return mixed
     */
    public function search(Builder $builder)
    {
        return $this->performSearch($builder, array_filter([
            'filter' => $this->filters($builder),
            'hitsPerPage' => $builder->limit,
            'sort' => $this->buildSortFromOrderByClauses($builder),
            'attributesToSearchOn' => $builder->attributesToSearchOn ?? [],
        ]));
    }

    /**
     * Perform the given search on the engine.
     *
     * page/hitsPerPage ensures that the search is exhaustive.
     *
     * @param  int  $perPage
     * @param  int  $page
     * @return mixed
     */
    public function paginate(Builder $builder, $perPage, $page)
    {
        return $this->performSearch($builder, array_filter([
            'filter' => $this->filters($builder),
            'hitsPerPage' => (int) $perPage,
            'page' => $page,
            'sort' => $this->buildSortFromOrderByClauses($builder),
            'attributesToSearchOn' => $builder->attributesToSearchOn ?? [],
        ]));
    }

    /**
     * Get the filter expression to be used with the query.
     *
     * @return string
     */
    public function filters(Builder $builder)
    {
        if (!is_array($builder->wheres) || empty($builder->wheres)) {
            return '';
        }

        $stack = [];

        foreach ($builder->wheres as $expression) {

            if (!empty($stack)) {
                $stack[] = strtoupper($expression['boolean']);
            }

            $type = $expression['type'];

            // Nested "( Expression )"
            if ($type === 'Nested' && array_key_exists('query', $expression)) {

                // Recursive nested expression
                $stack[] = '('.$this->filters($expression['query']).')';

            } else {

                // With NotNull/Null expressions we only need the column name
                $value = $expression['value'] ?? $expression['values'] ?? null;
                $column = $expression['column'];

                if ($type === 'Basic' && array_key_exists('operator', $expression)) {

                    $operator = $expression['operator'];
                    $stack[] = $this->parseFilterExpressions($column, $value, $operator);

                } else {

                    if ($type === 'between') {
                        $stack[] = $this->parseFilterExpressions($column, $value, $type);
                    } elseif (in_array($type, ['Exists', 'NotExists'])) {
                        $stack[] = $this->parseFilterExpressions($column, $value, $type);
                    } elseif (in_array($type, ['IsEmpty', 'IsNotEmpty'])) {
                        $stack[] = $this->parseFilterExpressions($column, $value, $type);
                    } elseif (in_array($type, ['In', 'NotIn'])) {
                        $stack[] = $this->parseFilterExpressions($column, $value, $type);
                    } elseif (in_array($type, ['Null', 'NotNull'])) {
                        $stack[] = $this->parseFilterExpressions($column, null, $type);
                    } else {
                        throw new InvalidArgumentException("{$type} expression not supported");
                    }

                }

            }

        }

        return implode(' ', $stack);
    }

    /**
     * @param  mixed  $value
     * @return string
     */
    protected function formatFilterValues($value)
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (is_numeric($value))
            ? sprintf('%s', $value)
            : sprintf('"%s"', $value);
    }

    /**
     * @param string $column
     * @param mixed $value
     * @param string|null $operator
     * @return string
     */
    protected function parseFilterExpressions($column, $value, $operator = null)
    {
        if ($operator === 'Exists') {
            return sprintf('%s EXISTS', $column);
        }

        if ($operator === 'NotExists') {
            return sprintf('NOT %s EXISTS', $column);
        }

        if (in_array($operator, ['Null', 'NotNull'])) {
            return sprintf('%s %s',
                $column,
                $operator === 'Null' ? 'IS NULL' : 'IS NOT NULL'
            );
        }

        // Note: Meilisearch does not treat null values as empty. To match null fields, use the IS NULL operator.
        if (in_array($operator, ['IsEmpty', 'IsNotEmpty'])) {
            return sprintf('%s %s',
                $column,
                $operator === 'IsEmpty' ? 'IS EMPTY' : 'IS NOT EMPTY'
            );
        }

        if (is_array($value)) {

            // Meilisearch uses "TO" operator as equivalent to >= AND <=
            if ($operator === 'between') {
                return sprintf('%s %s TO %s',
                    $column,
                    $this->formatFilterValues($value[0]),
                    $this->formatFilterValues($value[1]),
                );
            }

            // Where IN/NOT IN
            if (in_array($operator, ['In', 'NotIn'])) {
                return sprintf('%s %s [%s]',
                    $column,
                    $operator === 'In' ? 'IN' : 'NOT IN',
                    implode(', ', collect($value)->map(fn($v) => $this->formatFilterValues($v))->toArray())
                );
            }
        }

        if (empty($operator)) {
            $operator = '=';
        }

        return sprintf('%s%s%s', $column, $operator, $this->formatFilterValues($value));
    }
}
