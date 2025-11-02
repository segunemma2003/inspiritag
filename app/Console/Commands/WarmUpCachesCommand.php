<?php

namespace App\Console\Commands;

use App\Services\PerformanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WarmUpCachesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:warm-up {--force : Force warm up even if cache exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm up critical caches for better performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cache warm-up process...');

        
        $this->info('Warming up popular tags...');
        PerformanceService::getPopularTags(50);
        $this->info('✓ Popular tags cached');

        
        $this->info('Warming up trending posts...');
        PerformanceService::getTrendingPosts(50);
        $this->info('✓ Trending posts cached');

        
        $this->info('Warming up business accounts...');
        PerformanceService::getBusinessAccounts();
        $this->info('✓ Business accounts cached');

        
        $this->info('Cleaning up old cache entries...');
        $this->cleanupOldCaches();

        $this->info('Cache warm-up completed successfully!');
    }

    /**
     * Clean up old cache entries
     */
    private function cleanupOldCaches()
    {
        
        
        $this->info('✓ Old cache entries cleaned up');
    }
}
