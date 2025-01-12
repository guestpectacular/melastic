<?php

namespace Guestpectacular\Melastic\Tests;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Collection;
use Laravel\Scout\Builder;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\MeilisearchEngine;
use Laravel\Scout\Jobs\RemoveableScoutCollection;
use Laravel\Scout\Jobs\RemoveFromSearch;
use Meilisearch\Client as SearchClient;
use Meilisearch\Contracts\IndexesResults;
use Meilisearch\Endpoints\Indexes;
use Meilisearch\Search\SearchResult;
use Mockery as m;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\App\Models\Chirp;
use Workbench\App\Models\SearchableUser;
use Workbench\Database\Factories\ChirpFactory;
use Workbench\Database\Factories\SearchableUserFactory;

use function Orchestra\Testbench\after_resolving;

#[WithConfig('scout.driver', 'melastic-testing')]
#[WithMigration]
class MelasticEngineTest
{

}