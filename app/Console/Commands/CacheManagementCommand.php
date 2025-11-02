<?php

namespace App\Console\Commands;

use App\Services\PerformanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class CacheManagementCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:manage
                            {action : The action to perform (status|clear|warm|monitor|stats)}
                            {--pattern=* : Cache key pattern for selective operations}
                            {--force : Force operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage application cache (status, clear, warm, monitor, stats)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'status':
                $this->showCacheStatus();
                break;
            case 'clear':
                $this->clearCache();
                break;
            case 'warm':
                $this->warmCache();
                break;
            case 'monitor':
                $this->monitorCache();
                break;
            case 'stats':
                $this->showCacheStats();
                break;
            default:
                $this->error("Unknown action: {$action}");
                $this->line("Available actions: status, clear, warm, monitor, stats");
                return 1;
        }

        return 0;
    }

    /**
     * Show cache status
     */
    private function showCacheStatus()
    {
        $this->info('💾 Cache Status - Inspirtag API');
        $this->line('');

        try {
            
            $testKey = 'cache_test_' . time();
            $testValue = 'test_value';

            $start = microtime(true);
            Cache::put($testKey, $testValue, 60);
            $putTime = round((microtime(true) - $start) * 1000, 2);

            $start = microtime(true);
            $retrieved = Cache::get($testKey);
            $getTime = round((microtime(true) - $start) * 1000, 2);

            Cache::forget($testKey);

            $this->line("   Connection: ✅ Connected");
            $this->line("   Write Time: {$putTime}ms");
            $this->line("   Read Time: {$getTime}ms");

            
            $driver = config('cache.default');
            $this->line("   Driver: {$driver}");

            
            if ($driver === 'redis') {
                $this->showRedisInfo();
            }

        } catch (\Exception $e) {
            $this->error("   Status: ❌ Cache error - {$e->getMessage()}");
        }

        $this->line('');
    }

    /**
     * Show Redis information
     */
    private function showRedisInfo()
    {
        try {
            $redis = Redis::connection();
            $info = $redis->info();

            $this->line("   Redis Version: " . ($info['redis_version'] ?? 'Unknown'));
            $this->line("   Memory Used: " . $this->formatBytes($info['used_memory'] ?? 0));
            $this->line("   Memory Peak: " . $this->formatBytes($info['used_memory_peak'] ?? 0));
            $this->line("   Connected Clients: " . ($info['connected_clients'] ?? 0));
            $this->line("   Total Commands: " . ($info['total_commands_processed'] ?? 0));

        } catch (\Exception $e) {
            $this->error("   Redis Info: ❌ Error - {$e->getMessage()}");
        }
    }

    /**
     * Clear cache
     */
    private function clearCache()
    {
        $this->info('🧹 Clearing Cache');

        $pattern = $this->option('pattern');
        $force = $this->option('force');

        if (!$force && !$this->confirm('Are you sure you want to clear the cache?')) {
            $this->info('Cache clear cancelled.');
            return;
        }

        try {
            if ($pattern === '*') {
                
                Cache::flush();
                $this->line("   Status: ✅ All cache cleared");
            } else {
                
                $this->clearCachePattern($pattern);
            }

        } catch (\Exception $e) {
            $this->error("   Error: ❌ Failed to clear cache - {$e->getMessage()}");
        }

        $this->line('');
    }

    /**
     * Clear cache by pattern
     */
    private function clearCachePattern($pattern)
    {
        try {
            $redis = Redis::connection();
            $keys = $redis->keys($pattern);

            if (!empty($keys)) {
                $redis->del($keys);
                $this->line("   Status: ✅ Cleared " . count($keys) . " cache entries");
            } else {
                $this->line("   Status: ⚠️  No cache entries found for pattern: {$pattern}");
            }

        } catch (\Exception $e) {
            $this->error("   Error: ❌ Failed to clear cache pattern - {$e->getMessage()}");
        }
    }

    /**
     * Warm up cache
     */
    private function warmCache()
    {
        $this->info('🔥 Warming Up Cache');

        try {
            
            $this->line("   Warming up popular tags...");
            PerformanceService::getPopularTags(50);
            $this->line("   ✅ Popular tags cached");

            
            $this->line("   Warming up trending posts...");
            PerformanceService::getTrendingPosts(50);
            $this->line("   ✅ Trending posts cached");

            
            $this->line("   Warming up business accounts...");
            PerformanceService::getBusinessAccounts();
            $this->line("   ✅ Business accounts cached");

            $this->line("   Status: ✅ Cache warmed up successfully");

        } catch (\Exception $e) {
            $this->error("   Error: ❌ Failed to warm cache - {$e->getMessage()}");
        }

        $this->line('');
    }

    /**
     * Monitor cache
     */
    private function monitorCache()
    {
        $this->info('📊 Cache Monitoring');

        try {
            $redis = Redis::connection();
            $info = $redis->info();

            $this->line("   Cache Hit Rate: " . $this->calculateHitRate($info));
            $this->line("   Memory Usage: " . $this->formatBytes($info['used_memory'] ?? 0));
            $this->line("   Memory Peak: " . $this->formatBytes($info['used_memory_peak'] ?? 0));
            $this->line("   Connected Clients: " . ($info['connected_clients'] ?? 0));
            $this->line("   Total Commands: " . ($info['total_commands_processed'] ?? 0));
            $this->line("   Commands/sec: " . ($info['instantaneous_ops_per_sec'] ?? 0));

        } catch (\Exception $e) {
            $this->error("   Error: ❌ Failed to monitor cache - {$e->getMessage()}");
        }

        $this->line('');
    }

    /**
     * Show cache statistics
     */
    private function showCacheStats()
    {
        $this->info('📈 Cache Statistics');

        try {
            $redis = Redis::connection();
            $info = $redis->info();

            $this->line("   Redis Statistics:");
            $this->line("     Version: " . ($info['redis_version'] ?? 'Unknown'));
            $this->line("     Uptime: " . $this->formatUptime($info['uptime_in_seconds'] ?? 0));
            $this->line("     Memory Used: " . $this->formatBytes($info['used_memory'] ?? 0));
            $this->line("     Memory Peak: " . $this->formatBytes($info['used_memory_peak'] ?? 0));
            $this->line("     Memory Fragmentation: " . ($info['mem_fragmentation_ratio'] ?? 0));
            $this->line("     Connected Clients: " . ($info['connected_clients'] ?? 0));
            $this->line("     Total Commands: " . ($info['total_commands_processed'] ?? 0));
            $this->line("     Commands/sec: " . ($info['instantaneous_ops_per_sec'] ?? 0));
            $this->line("     Keyspace Hits: " . ($info['keyspace_hits'] ?? 0));
            $this->line("     Keyspace Misses: " . ($info['keyspace_misses'] ?? 0));

            
            $hits = $info['keyspace_hits'] ?? 0;
            $misses = $info['keyspace_misses'] ?? 0;
            $total = $hits + $misses;

            if ($total > 0) {
                $hitRate = round(($hits / $total) * 100, 2);
                $this->line("     Hit Rate: {$hitRate}%");
            } else {
                $this->line("     Hit Rate: N/A");
            }

        } catch (\Exception $e) {
            $this->error("   Error: ❌ Failed to get cache stats - {$e->getMessage()}");
        }

        $this->line('');
    }

    /**
     * Calculate cache hit rate
     */
    private function calculateHitRate($info)
    {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;

        if ($total > 0) {
            return round(($hits / $total) * 100, 2) . '%';
        }

        return 'N/A';
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Format uptime to human readable format
     */
    private function formatUptime($seconds)
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return "{$days}d {$hours}h {$minutes}m";
    }
}
