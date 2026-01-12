<?php

namespace Holibob\Search\Console;

use Holibob\Search\Services\SearchService;
use Illuminate\Console\Command;

class ReindexCommand extends Command
{
    protected $signature = 'search:reindex
                            {model? : The model to reindex (properties, all)}
                            {--clear : Clear the index before reindexing}';

    protected $description = 'Reindex searchable models';

    public function handle(SearchService $searchService): int
    {
        $model = $this->argument('model') ?? 'properties';

        if ($this->option('clear')) {
            $this->info('Clearing search index...');
            $searchService->clearIndex();
            $this->info('Search index cleared.');
        }

        $this->info("Reindexing {$model}...");

        try {
            $count = match ($model) {
                'properties', 'all' => $searchService->reindexProperties(),
                default => throw new \InvalidArgumentException("Unknown model: {$model}"),
            };

            $this->info("Successfully reindexed {$count} records.");

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Reindex failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
