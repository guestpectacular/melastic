<?php

namespace Guestpectacular\Melastic;

use Laravel\Scout\EngineManager as ScoutEngineManager;
use Guestpectacular\Melastic\Engines\MeilisearchEngine;
use Meilisearch\Client as MeilisearchClient;

class EngineManager extends ScoutEngineManager{

    public function createMelasticDriver()
    {
        return new MeilisearchEngine(
            $this->container->make(MeilisearchClient::class),
            config('scout.soft_delete', false)
        );
    }

    protected function ensureAlgoliaClientIsInstalled()
    {
        return;
    }

}