<?php

namespace Guestpectacular\Melastic;

use Illuminate\Support\ServiceProvider;
use Meilisearch\Client as Meilisearch;
class MelasticServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/scout.php', 'scout');

        $this->app->singleton(Meilisearch::class, function ($app) {

            /**
             * Is this step really need? I don't want to overwrite any existent config/scout.php
             * file from users.
             */
            $app['config']->set('scout.engine', 'meilisearch');

            $config = $app['config']->get('scout.meilisearch');

            return new Meilisearch(
                $config['host'],
                $config['key'],
                clientAgents: [sprintf('Melastic (Meilisearch + Eloquent) (v%s)', Melastic::VERSION)],
            );

        });

//        $this->app->singleton(EngineManager::class, function ($app) {
//            return new EngineManager($app);
//        });
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {

            $this->publishes([
                __DIR__.'/../config/scout.php' => $this->app['path.config'].DIRECTORY_SEPARATOR.'scout.php',
            ]);

        }
    }

}
