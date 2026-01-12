<?php

namespace Holibob\Affiliates;

use Holibob\Affiliates\Console\SyncAffiliateCommand;
use Illuminate\Support\ServiceProvider;

class AffiliatesServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/affiliates.php',
            'affiliates'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/affiliates.php' => config_path('affiliates.php'),
        ], 'affiliates-config');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncAffiliateCommand::class,
            ]);
        }
    }
}
