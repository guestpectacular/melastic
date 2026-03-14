<?php

namespace Guestpectacular\Melastic;

use Laravel\Scout\Searchable as ScoutSearchable;

trait Searchable
{
    use ScoutSearchable;

    /**
     * Perform a search against the model's indexed data.
     *
     * Overridden to ensure Melastic's Builder is instantiated instead of Scout's.
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

    /**
     * Get the engine used to index the model.
     *
     * Overridden to resolve Melastic's EngineManager directly, bypassing Scout's
     * singleton which gets registered after this provider and would override it.
     *
     * @return \Guestpectacular\Melastic\Engines\MeilisearchEngine
     */
    public function searchableUsing()
    {
        return app(EngineManager::class)->engine();
    }
}
