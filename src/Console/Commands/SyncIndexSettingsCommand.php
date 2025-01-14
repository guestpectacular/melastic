<?php

namespace Guestpectacular\Melastic\Console\Commands;

use Guestpectacular\Melastic\EngineManager;
use Laravel\Scout\EngineManager as ScoutEngineManager;
class SyncIndexSettingsCommand extends \Laravel\Scout\Console\SyncIndexSettingsCommand
{
    public function handle(ScoutEngineManager $manager)
    {
        $manager = app(EngineManager::class);
        parent::handle($manager);
    }
}

