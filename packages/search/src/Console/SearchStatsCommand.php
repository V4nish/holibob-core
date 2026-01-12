<?php

namespace Holibob\Search\Console;

use Holibob\Search\Services\SearchService;
use Illuminate\Console\Command;

class SearchStatsCommand extends Command
{
    protected $signature = 'search:stats
                            {--days=30 : Number of days to analyze}';

    protected $description = 'Display search statistics and analytics';

    public function handle(SearchService $searchService): int
    {
        $days = (int) $this->option('days');

        $this->info("Search Statistics (Last {$days} days)");
        $this->line('');

        // Overall statistics
        $stats = $searchService->getStatistics($days);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Searches', number_format($stats['total_searches'])],
                ['Unique Queries', number_format($stats['unique_queries'])],
                ['Avg Results per Search', $stats['avg_results']],
                ['Zero Result Rate', $stats['zero_result_rate'] . '%'],
            ]
        );

        $this->line('');

        // Popular queries
        $this->info('Top 10 Search Queries:');
        $popular = $searchService->popularQueries(10, $days);

        if (! empty($popular)) {
            $this->table(
                ['Query', 'Count'],
                collect($popular)->map(fn ($count, $query) => [$query, number_format($count)])->toArray()
            );
        } else {
            $this->line('No search queries found.');
        }

        $this->line('');

        // Empty searches
        $this->info('Top 10 Queries with Zero Results:');
        $empty = $searchService->emptySearches(10, $days);

        if (! empty($empty)) {
            $this->table(
                ['Query', 'Count'],
                collect($empty)->map(fn ($count, $query) => [$query, number_format($count)])->toArray()
            );
        } else {
            $this->line('No zero-result queries found.');
        }

        return self::SUCCESS;
    }
}
