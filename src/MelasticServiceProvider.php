<?php

namespace Guestpectacular\Melastic;

use Guestpectacular\Melastic\EngineManager as MelasticEngineManager;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\Console\DeleteAllIndexesCommand;
use Laravel\Scout\Console\DeleteIndexCommand;
use Laravel\Scout\Console\FlushCommand;
use Laravel\Scout\Console\ImportCommand;
use Laravel\Scout\Console\IndexCommand;
use Laravel\Scout\Console\SyncIndexSettingsCommand;

class MelasticServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/scout.php', 'scout');

        // Register under Melastic's own class name to avoid conflicts with
        // Scout's ScoutEngineManager binding which loads after this provider.
        $this->app->singleton(MelasticEngineManager::class, function ($app) {
            return new MelasticEngineManager($app);
        });
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {

            $this->publishes([
                __DIR__.'/../config/scout.php' => $this->app['path.config'].DIRECTORY_SEPARATOR.'scout.php',
            ]);

            $this->commands([
                FlushCommand::class,
                ImportCommand::class,
                IndexCommand::class,
                DeleteIndexCommand::class,
                DeleteAllIndexesCommand::class,
                SyncIndexSettingsCommand::class,
            ]);

        }
    }
}
