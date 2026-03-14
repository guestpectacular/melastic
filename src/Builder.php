<?php

namespace Guestpectacular\Melastic;

use Closure;
use Guestpectacular\Melastic\Engines\MeilisearchEngine;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Laravel\Scout\Builder as ScoutBuilder;

class Builder extends ScoutBuilder
{
    /**
     * All clause operators supported by Meilisearch.
     *
     * @var string[]
     */
    public $operators = [
        '=', '!=', '>', '>=', '<', '<=', 'TO', 'EXISTS', 'IN', 'NOT', 'AND', 'OR',
    ];

    public array $attributesToSearchOn = [];

    /**
     * Set the "limit" for the search query.
     *
     * @param int $limit
     * @return $this
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @param string|array $attributes
     * @return $this
     */
    public function attributesToSearchOn(string|array $attributes = []): self
    {
        if (!is_array($attributes)) {
            $attributes = [$attributes];
        }
        $this->attributesToSearchOn = $attributes;

        return $this;
    }

    /**
     * Add a full sub-select to the query.
     *
     * @param Closure|string|array $column
     * @param mixed $operator
     * @param mixed $value
     * @param string $boolean
     * @return $this
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and'): self
    {
        if (is_array($column)) {
            return $this->addArrayOfWheres($column, $boolean);
        }

        [$value, $operator] = $this->prepareValueAndOperator($value, $operator, func_num_args() === 2);

        if ($column instanceof Closure && is_null($operator)) {
            return $this->whereNested($column, $boolean);
        }

        if ($this->invalidOperator($operator)) {
            [$value, $operator] = [$operator, '='];
        }

        if (is_null($value)) {
            return $this->whereNull($column, $boolean, $operator !== '=');
        }

        $type = 'Basic';

        $this->wheres[] = compact('type', 'column', 'operator', 'value', 'boolean');

        return $this;
    }

    /**
     * Add a "where in" clause to the query.
     *
     * @param string $column
     * @param mixed $values
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function whereIn($column, $values, $boolean = 'and', $not = false)
    {
        $type = $not ? 'NotIn' : 'In';

        if ($values instanceof Arrayable) {
            $values = $values->toArray();
        } elseif (!is_array($values)) {
            $values = [$values];
        }

        if (is_array($values) && count($values) !== count(Arr::flatten($values, 1))) {
            throw new InvalidArgumentException('Nested arrays may not be passed to whereIn method.');
        }

        $this->wheres[] = compact('type', 'column', 'values', 'boolean');

        return $this;
    }

    /**
     * Add an "or where in" clause to the query.
     *
     * @param string $column
     * @param mixed $values
     * @return $this
     */
    public function orWhereIn($column, $values)
    {
        return $this->whereIn($column, $values, 'or');
    }

    /**
     * Add a "where not in" clause to the query.
     *
     * @param string $column
     * @param mixed $values
     * @param string $boolean
     * @return $this
     */
    public function whereNotIn($column, $values, $boolean = 'and')
    {
        return $this->whereIn($column, $values, $boolean, true);
    }

    /**
     * Add an "or where not in" clause to the query.
     *
     * @param string $column
     * @param mixed $values
     * @return $this
     */
    public function orWhereNotIn($column, $values)
    {
        return $this->whereIn($column, $values, 'or', true);
    }

    /**
     * Add an exists clause to the query.
     *
     * @param string $column
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function addWhereExists($column, $boolean = 'and', $not = false)
    {
        $type = $not ? 'NotExists' : 'Exists';

        $this->wheres[] = compact('type', 'column', 'boolean');

        return $this;
    }

    /**
     * @param string $column
     * @param string $boolean
     * @return $this
     */
    public function whereExists($column, $boolean = 'and')
    {
        return $this->addWhereExists($column, $boolean);
    }

    /**
     * @param string $column
     * @return $this
     */
    public function orWhereExists($column)
    {
        return $this->whereExists($column, 'or');
    }

    /**
     * @param string $column
     * @param string $boolean
     * @return $this
     */
    public function whereNotExists($column, $boolean = 'and')
    {
        return $this->addWhereExists($column, $boolean, true);
    }

    /**
     * @param string $column
     * @return $this
     */
    public function orWhereNotExists($column)
    {
        return $this->addWhereExists($column, 'or', true);
    }

    /**
     * Add a where between statement to the query.
     *
     * @param string $column
     * @param array<int, mixed> $values
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function whereBetween($column, $values, $boolean = 'and', $not = false)
    {
        if ($values instanceof Arrayable) {
            $values = $values->toArray();
        } elseif (!is_array($values)) {
            $values = [$values];
        }

        if (count($values) !== 2 || count($values, COUNT_RECURSIVE) !== 2) {
            throw new InvalidArgumentException('Between only supports an array with two values.');
        }

        $values = array_values($values);

        $type = 'between';

        $this->wheres[] = compact('type', 'column', 'values', 'boolean', 'not');

        return $this;
    }

    /**
     * Add an "or where" clause to the query.
     *
     * @param Closure|string|array $column
     * @param mixed $operator
     * @param mixed $value
     * @return $this
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        return $this->where($column, $operator, $value, 'or');
    }

    /**
     * Add a "filter null" clause to the query.
     *
     * @param string $columns
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function whereNull($columns, $boolean = 'and', $not = false)
    {
        $type = $not ? 'NotNull' : 'Null';

        foreach (Arr::wrap($columns) as $column) {
            $this->wheres[] = compact('type', 'column', 'boolean');
        }

        return $this;
    }

    /**
     * @param string|array $column
     * @return $this
     */
    public function orWhereNull($column)
    {
        return $this->whereNull($column, 'or');
    }

    /**
     * @param string|array $columns
     * @param string $boolean
     * @return $this
     */
    public function whereNotNull($columns, $boolean = 'and')
    {
        return $this->whereNull($columns, $boolean, true);
    }

    /**
     * @param string $column
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function addWhereIsEmpty($column, $boolean = 'and', $not = false)
    {
        $type = $not ? 'IsNotEmpty' : 'IsEmpty';

        $this->wheres[] = compact('type', 'column', 'boolean');

        return $this;
    }

    /**
     * @param string $column
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function whereIsEmpty($column, $boolean = 'and', $not = false)
    {
        return $this->addWhereIsEmpty($column, $boolean, $not);
    }

    /**
     * @param string $column
     * @return $this
     */
    public function orWhereIsEmpty($column)
    {
        return $this->addWhereIsEmpty($column, 'or');
    }

    /**
     * @param string $column
     * @param string $boolean
     * @return $this
     */
    public function whereIsNotEmpty($column, $boolean = 'and')
    {
        return $this->addWhereIsEmpty($column, $boolean, true);
    }

    /**
     * Create a new query instance for nested filter condition.
     *
     * @return Builder
     */
    public function forNestedWhere()
    {
        return $this->newQuery();
    }

    /**
     * Add a nested filter statement to the query.
     *
     * @param string $boolean
     * @return $this
     */
    public function whereNested(Closure $callback, $boolean = 'and')
    {
        $callback($query = $this->forNestedWhere());

        return $this->addNestedWhereQuery($query, $boolean);
    }

    /**
     * Add another query builder as a nested where to the query builder.
     *
     * @param Builder $query
     * @param string $boolean
     * @return $this
     */
    public function addNestedWhereQuery($query, $boolean = 'and')
    {
        if (count($query->wheres)) {
            $type = 'Nested';

            $this->wheres[] = compact('type', 'query', 'boolean');
        }

        return $this;
    }

    /**
     * Get a new instance of the query builder.
     *
     * @return Builder
     */
    public function newQuery()
    {
        return new self(
            $this->model,
            null,
            null,
            in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($this->model))
        );
    }

    /**
     * Get the raw filter query string, equivalent to Eloquent's `toRawSql`.
     *
     * @return string
     */
    public function toRawFilters(): string
    {
        return $this->engine()->filters($this);
    }

    /**
     * Dump the current filter expression and die.
     */
    public function dd()
    {
        dd($this->engine()->filters($this));
    }

    /**
     * Determine if the given operator is supported.
     *
     * @param string $operator
     * @return bool
     */
    protected function invalidOperator($operator)
    {
        return !is_string($operator) || (!in_array(strtolower($operator), $this->operators, true));
    }

    /**
     * Determine if the given operator and value combination is legal.
     *
     * @param string $operator
     * @param mixed $value
     * @return bool
     */
    protected function invalidOperatorAndValue($operator, $value)
    {
        return is_null($value) && in_array($operator, $this->operators) && !in_array($operator, ['=', '!=']);
    }

    /**
     * Prepare the value and operator for a filter clause.
     *
     * @param string $value
     * @param string $operator
     * @param bool $useDefault
     * @return array
     *
     * @throws InvalidArgumentException
     */
    public function prepareValueAndOperator($value, $operator, $useDefault = false)
    {
        if ($useDefault) {
            return [$operator, '='];
        } elseif ($this->invalidOperatorAndValue($operator, $value)) {
            throw new InvalidArgumentException('Illegal operator and value combination.');
        }

        return [$value, $operator];
    }

    /**
     * Add an array of where clauses to the query.
     *
     * @param array $column
     * @param string $boolean
     * @param string $method
     * @return $this
     */
    protected function addArrayOfWheres($column, $boolean, $method = 'where')
    {
        return $this->whereNested(function ($query) use ($column, $method, $boolean) {
            foreach ($column as $key => $value) {
                if (is_numeric($key) && is_array($value)) {
                    $query->{$method}(...array_values($value));
                } else {
                    $query->{$method}($key, '=', $value, $boolean);
                }
            }
        }, $boolean);
    }

    /**
     * Get the engine that should handle the query.
     *
     * @return MeilisearchEngine
     */
    protected function engine(): MeilisearchEngine
    {
        return $this->model->searchableUsing();
    }
}
