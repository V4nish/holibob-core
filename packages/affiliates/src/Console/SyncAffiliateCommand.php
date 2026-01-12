<?php

namespace Holibob\Affiliates\Console;

use App\Models\AffiliateProvider;
use Holibob\Affiliates\Jobs\SyncPropertiesJob;
use Illuminate\Console\Command;

class SyncAffiliateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'affiliate:sync
                            {provider? : The slug of the affiliate provider to sync}
                            {--all : Sync all active providers}
                            {--sync : Run synchronously instead of queuing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync properties from affiliate providers';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('all')) {
            return $this->syncAll();
        }

        $providerSlug = $this->argument('provider');

        if (! $providerSlug) {
            $this->error('Please specify a provider slug or use --all flag');
            return self::FAILURE;
        }

        return $this->syncProvider($providerSlug);
    }

    /**
     * Sync all active providers.
     */
    protected function syncAll(): int
    {
        $providers = AffiliateProvider::where('is_active', true)->get();

        if ($providers->isEmpty()) {
            $this->warn('No active affiliate providers found');
            return self::SUCCESS;
        }

        $this->info("Syncing {$providers->count()} provider(s)...");

        foreach ($providers as $provider) {
            $this->syncProviderModel($provider);
        }

        $this->info('All syncs have been queued');

        return self::SUCCESS;
    }

    /**
     * Sync a specific provider by slug.
     */
    protected function syncProvider(string $slug): int
    {
        $provider = AffiliateProvider::where('slug', $slug)->first();

        if (! $provider) {
            $this->error("Provider '{$slug}' not found");
            return self::FAILURE;
        }

        if (! $provider->is_active) {
            $this->warn("Provider '{$slug}' is not active");
            return self::FAILURE;
        }

        $this->syncProviderModel($provider);

        $this->info("Sync queued for {$provider->name}");

        return self::SUCCESS;
    }

    /**
     * Dispatch sync job for provider.
     */
    protected function syncProviderModel(AffiliateProvider $provider): void
    {
        $this->line("→ Queueing sync for {$provider->name}");

        if ($this->option('sync')) {
            // Run synchronously
            SyncPropertiesJob::dispatchSync($provider);
            $this->info("  ✓ Sync completed for {$provider->name}");
        } else {
            // Queue it
            SyncPropertiesJob::dispatch($provider);
        }
    }
}
