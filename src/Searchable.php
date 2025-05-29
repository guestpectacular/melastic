<?php

namespace Guestpectacular\Melastic;

use Guestpectacular\Melastic\EngineManager as MelasticEngineManager;
use Laravel\Scout\Searchable as ScoutSearchable;

trait Searchable {

    use ScoutSearchable;

    public function searchableUsing()
    {
        return app(MelasticEngineManager::class)->engine();
    }

    /**
     * Perform a search against the model's indexed data.
     *
     * @param  string  $query
     * @param  \Closure  $callback
     * @return Builder<static>
     */
    public static function search($query = '', $callback = null)
    {
        return app(static::$scoutBuilder ?? Builder::class, [
            'model' => new static,
            'query' => $query,
            'callback' => $callback,
            'softDelete' => static::usesSoftDelete() && config('scout.soft_delete', false),
        ]);
    }

    public function getScoutModelsByIds(Builder $builder, array $ids)
    {
        return $this->queryScoutModelsByIds($builder, $ids)->get();
    }

    public function queryScoutModelsByIds(Builder $builder, array $ids)
    {
        $query = static::usesSoftDelete()
            ? $this->withTrashed() : $this->newQuery();

        if ($builder->queryCallback) {
            call_user_func($builder->queryCallback, $query);
        }

        $whereIn = in_array($this->getScoutKeyType(), ['int', 'integer']) ?
            'whereIntegerInRaw' :
            'whereIn';

        return $query->{$whereIn}(
            $this->qualifyColumn($this->getScoutKeyName()), $ids
        );
    }

}