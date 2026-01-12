<?php

namespace Holibob\Search;

use Holibob\Search\Console\ReindexCommand;
use Holibob\Search\Console\SearchStatsCommand;
use Holibob\Search\Services\SearchService;
use Illuminate\Support\ServiceProvider;

class SearchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(SearchService::class, function ($app) {
            return new SearchService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                ReindexCommand::class,
                SearchStatsCommand::class,
            ]);
        }
    }
}
