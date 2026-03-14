<?php

namespace Guestpectacular\Melastic\Tests;

use Guestpectacular\Melastic\Builder;
use Guestpectacular\Melastic\Engines\MeilisearchEngine;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Meilisearch\Client;
use PHPUnit\Framework\TestCase;

class MeilisearchEngineFiltersTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function engine(): MeilisearchEngine
    {
        return new MeilisearchEngine($this->createMock(Client::class));
    }

    private function builder(): Builder
    {
        $model = new class extends Model {
            protected $table = 'test';
        };

        return new Builder($model, '');
    }

    private function filters(Builder $builder): string
    {
        return $this->engine()->filters($builder);
    }

    // -------------------------------------------------------------------------
    // Empty
    // -------------------------------------------------------------------------

    public function test_empty_filter_when_no_wheres_are_added(): void
    {
        $this->assertSame('', $this->filters($this->builder()));
    }

    // -------------------------------------------------------------------------
    // Simple filters — Basic equality
    // -------------------------------------------------------------------------

    public function test_where_with_string_value(): void
    {
        $builder = $this->builder()->where('status', 'active');

        $this->assertSame('status="active"', $this->filters($builder));
    }

    public function test_where_with_numeric_integer_value(): void
    {
        $builder = $this->builder()->where('answer_to_the_ultimate_question_of_life_the_universe_and_everything', 42);

        $this->assertSame('answer_to_the_ultimate_question_of_life_the_universe_and_everything=42', $this->filters($builder));
    }

    public function test_where_with_numeric_float_value(): void
    {
        $builder = $this->builder()->where('rating', 4.5);

        $this->assertSame('rating=4.5', $this->filters($builder));
    }

    public function test_where_with_boolean_true(): void
    {
        $builder = $this->builder()->where('published', true);

        $this->assertSame('published=true', $this->filters($builder));
    }

    public function test_where_with_boolean_false(): void
    {
        $builder = $this->builder()->where('published', false);

        $this->assertSame('published=false', $this->filters($builder));
    }

    public function test_where_with_explicit_equals_operator(): void
    {
        $builder = $this->builder()->where('status', '=', 'active');

        $this->assertSame('status="active"', $this->filters($builder));
    }

    public function test_where_with_not_equals_operator(): void
    {
        $builder = $this->builder()->where('status', '!=', 'deleted');

        $this->assertSame('status!="deleted"', $this->filters($builder));
    }

    public function test_where_with_greater_than_operator(): void
    {
        $builder = $this->builder()->where('age', '>', 18);

        $this->assertSame('age>18', $this->filters($builder));
    }

    public function test_where_with_greater_than_or_equal_operator(): void
    {
        $builder = $this->builder()->where('age', '>=', 18);

        $this->assertSame('age>=18', $this->filters($builder));
    }

    public function test_where_with_less_than_operator(): void
    {
        $builder = $this->builder()->where('age', '<', 65);

        $this->assertSame('age<65', $this->filters($builder));
    }

    public function test_where_with_less_than_or_equal_operator(): void
    {
        $builder = $this->builder()->where('age', '<=', 65);

        $this->assertSame('age<=65', $this->filters($builder));
    }

    // -------------------------------------------------------------------------
    // WHERE IN / NOT IN
    // -------------------------------------------------------------------------

    public function test_where_in_with_string_values(): void
    {
        $builder = $this->builder()->whereIn('status', ['active', 'pending']);

        $this->assertSame('status IN ["active", "pending"]', $this->filters($builder));
    }

    public function test_where_in_with_integer_values(): void
    {
        $builder = $this->builder()->whereIn('id', [1, 2, 3]);

        $this->assertSame('id IN [1, 2, 3]', $this->filters($builder));
    }

    public function test_where_in_with_boolean_values(): void
    {
        $builder = $this->builder()->whereIn('active', [true, false]);

        $this->assertSame('active IN [true, false]', $this->filters($builder));
    }

    public function test_where_not_in_with_string_values(): void
    {
        $builder = $this->builder()->whereNotIn('status', ['deleted', 'banned']);

        $this->assertSame('status NOT IN ["deleted", "banned"]', $this->filters($builder));
    }

    public function test_where_not_in_with_integer_values(): void
    {
        $builder = $this->builder()->whereNotIn('id', [10, 20]);

        $this->assertSame('id NOT IN [10, 20]', $this->filters($builder));
    }

    // -------------------------------------------------------------------------
    // IS NULL / IS NOT NULL
    // -------------------------------------------------------------------------

    public function test_where_null(): void
    {
        $builder = $this->builder()->whereNull('deleted_at');

        $this->assertSame('deleted_at IS NULL', $this->filters($builder));
    }

    public function test_where_not_null(): void
    {
        $builder = $this->builder()->whereNotNull('deleted_at');

        $this->assertSame('deleted_at IS NOT NULL', $this->filters($builder));
    }

    public function test_or_where_null(): void
    {
        $builder = $this->builder()->whereNull('published_at')->orWhereNull('featured_at');

        $this->assertSame('published_at IS NULL OR featured_at IS NULL', $this->filters($builder));
    }

    // -------------------------------------------------------------------------
    // IS EMPTY / IS NOT EMPTY
    // -------------------------------------------------------------------------

    public function test_where_is_empty(): void
    {
        $builder = $this->builder()->whereIsEmpty('tags');

        $this->assertSame('tags IS EMPTY', $this->filters($builder));
    }

    public function test_where_is_not_empty(): void
    {
        $builder = $this->builder()->whereIsNotEmpty('tags');

        $this->assertSame('tags IS NOT EMPTY', $this->filters($builder));
    }

    public function test_or_where_is_empty(): void
    {
        $builder = $this->builder()->whereIsEmpty('tags')->orWhereIsEmpty('categories');

        $this->assertSame('tags IS EMPTY OR categories IS EMPTY', $this->filters($builder));
    }

    // -------------------------------------------------------------------------
    // EXISTS / NOT EXISTS
    // -------------------------------------------------------------------------

    public function test_where_exists(): void
    {
        $builder = $this->builder()->whereExists('metadata');

        $this->assertSame('metadata EXISTS', $this->filters($builder));
    }

    public function test_where_not_exists(): void
    {
        $builder = $this->builder()->whereNotExists('metadata');

        $this->assertSame('NOT metadata EXISTS', $this->filters($builder));
    }

    public function test_or_where_exists(): void
    {
        $builder = $this->builder()->whereExists('cover')->orWhereExists('thumbnail');

        $this->assertSame('cover EXISTS OR thumbnail EXISTS', $this->filters($builder));
    }

    public function test_or_where_not_exists(): void
    {
        $builder = $this->builder()->whereNotExists('cover')->orWhereNotExists('thumbnail');

        $this->assertSame('NOT cover EXISTS OR NOT thumbnail EXISTS', $this->filters($builder));
    }

    // -------------------------------------------------------------------------
    // BETWEEN (TO operator)
    // -------------------------------------------------------------------------

    public function test_where_between_with_integers(): void
    {
        $builder = $this->builder()->whereBetween('price', [10, 100]);

        $this->assertSame('price 10 TO 100', $this->filters($builder));
    }

    public function test_where_between_with_floats(): void
    {
        $builder = $this->builder()->whereBetween('rating', [1.5, 4.9]);

        $this->assertSame('rating 1.5 TO 4.9', $this->filters($builder));
    }

    // -------------------------------------------------------------------------
    // Boolean logic — AND / OR
    // -------------------------------------------------------------------------

    public function test_multiple_wheres_are_joined_with_and(): void
    {
        $builder = $this->builder()
            ->where('status', 'active')
            ->where('age', '>', 41);

        $this->assertSame('status="active" AND age>41', $this->filters($builder));
    }

    public function test_or_where_joins_with_or(): void
    {
        $builder = $this->builder()
            ->where('status', 'active')
            ->orWhere('status', 'pending');

        $this->assertSame('status="active" OR status="pending"', $this->filters($builder));
    }

    public function test_mixed_and_or_operators(): void
    {
        $builder = $this->builder()
            ->where('published', true)
            ->where('age', '>', 41)
            ->orWhere('featured', true);

        $this->assertSame('published=true AND age>41 OR featured=true', $this->filters($builder));
    }

    // -------------------------------------------------------------------------
    // Nested filters
    // -------------------------------------------------------------------------

    public function test_nested_where_wraps_in_parentheses(): void
    {
        $builder = $this->builder()->where(function ($query) {
            $query->where('category', 'music')->where('year', '>', 1985);
        });

        $this->assertSame('(category="music" AND year>1985)', $this->filters($builder));
    }

    public function test_nested_where_with_or_inside(): void
    {
        $builder = $this->builder()->where(function ($query) {
            $query->where('category', 'music')->orWhere('category', 'rock');
        });

        $this->assertSame('(category="music" OR category="rock")', $this->filters($builder));
    }

    public function test_nested_where_combined_with_outer_conditions(): void
    {
        $builder = $this->builder()
            ->where('published', true)
            ->where(function ($query) {
                $query->where('category', 'music')->orWhere('category', 'rock');
            });

        $this->assertSame('published=true AND (category="music" OR category="rock")', $this->filters($builder));
    }

    public function test_multiple_nested_groups_joined_with_or(): void
    {
        $builder = $this->builder()
            ->where(function ($query) {
                $query->where('artist_id', 1)->where('year', '>', 1985);
            })
            ->orWhere(function ($query) {
                $query->where('artist_id', 2)->where('featured', true);
            });

        $this->assertSame('(artist_id=1 AND year>1985) OR (artist_id=2 AND featured=true)', $this->filters($builder));
    }

    public function test_deeply_nested_three_levels(): void
    {
        $builder = $this->builder()
            ->where('published', true)
            ->where(function ($query) {
                $query->where('status', 'active')
                    ->orWhere(function ($query) {
                        $query->where('featured', true)->where('age', '>=', 41);
                    });
            });

        $this->assertSame(
            'published=true AND (status="active" OR (featured=true AND age>=41))',
            $this->filters($builder)
        );
    }

    // -------------------------------------------------------------------------
    // Complex real-world combinations
    // -------------------------------------------------------------------------

    public function test_combining_in_and_range_and_null(): void
    {
        $builder = $this->builder()
            ->whereIn('category_id', [1, 2, 3])
            ->whereBetween('price', [10, 500])
            ->whereNotNull('published_at');

        $this->assertSame(
            'category_id IN [1, 2, 3] AND price 10 TO 500 AND published_at IS NOT NULL',
            $this->filters($builder)
        );
    }

    public function test_combining_exists_and_not_in(): void
    {
        $builder = $this->builder()
            ->whereExists('cover_image')
            ->whereNotIn('status', ['draft', 'archived']);

        $this->assertSame(
            'cover_image EXISTS AND status NOT IN ["draft", "archived"]',
            $this->filters($builder)
        );
    }

    public function test_combining_nested_with_in_and_exists(): void
    {
        $builder = $this->builder()
            ->where(function ($query) {
                $query->whereIn('genre', ['rock', 'pop'])->orWhereExists('featured');
            })
            ->where('published', true);

        $this->assertSame(
            '(genre IN ["rock", "pop"] OR featured EXISTS) AND published=true',
            $this->filters($builder)
        );
    }

    // -------------------------------------------------------------------------
    // Unsupported expressions throw
    // -------------------------------------------------------------------------

    public function test_unsupported_expression_type_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Manually inject an unsupported expression type to verify the guard
        $builder = $this->builder();
        $builder->wheres[] = ['type' => 'UnsupportedType', 'column' => 'field', 'boolean' => 'and'];

        $this->filters($builder);
    }
}
