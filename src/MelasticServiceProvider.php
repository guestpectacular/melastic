<?php

namespace Guestpectacular\Melastic;

use Guestpectacular\Melastic\Console\Commands\SyncIndexSettingsCommand;
use Guestpectacular\Melastic\EngineManager as MelasticEngineManager;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\Console\DeleteAllIndexesCommand;
use Laravel\Scout\Console\DeleteIndexCommand;
use Laravel\Scout\Console\FlushCommand;
use Laravel\Scout\Console\ImportCommand;
use Laravel\Scout\Console\IndexCommand;

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

//        $this->app->bind('command.scout:sync-index-settings', SyncIndexSettingsCommand::class);

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

            $this->commands([
                FlushCommand::class,
                ImportCommand::class,
                IndexCommand::class,
//                \Laravel\Scout\Console\SyncIndexSettingsCommand::class,
                DeleteIndexCommand::class,
                DeleteAllIndexesCommand::class,
                SyncIndexSettingsCommand::class,
            ]);

        }
    }
}