<?php

namespace Guestpectacular\Melastic;

use Guestpectacular\Melastic\EngineManager as MelasticEngineManager;
use Illuminate\Support\ServiceProvider;

class MelasticServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {

        $this->mergeConfigFrom(__DIR__.'/../config/scout.php', 'scout');

        $this->app->singleton(MelasticEngineManager::class, function ($app) {
            return new MelasticEngineManager($app);
        });

    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {

            $this->publishes([
                __DIR__.'/../config/scout.php' => $this->app['path.config'].DIRECTORY_SEPARATOR.'scout.php',
            ]);

        }
    }
}