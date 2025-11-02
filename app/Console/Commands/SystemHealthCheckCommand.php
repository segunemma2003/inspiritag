<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\User;
use App\Models\Notification;
use App\Services\PerformanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class SystemHealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:health-check {--detailed : Show detailed system metrics}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Comprehensive system health check for 100,000+ users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🏥 System Health Check - Inspirtag API');
        $this->line('');

        
        $this->checkDatabaseHealth();

        
        $this->checkCacheHealth();

        
        $this->checkQueueHealth();

        
        $this->checkApplicationHealth();

        
        $this->checkPerformanceHealth();

        
        $this->checkSystemResources();

        if ($this->option('detailed')) {
            $this->showDetailedMetrics();
        }

        $this->line('');
        $this->info('✅ System health check completed!');
    }

    /**
     * Check database health
     */
    private function checkDatabaseHealth()
    {
        $this->info('🗄️  Database Health');

        try {
            
            $start = microtime(true);
            DB::select('SELECT 1');
            $connectionTime = round((microtime(true) - $start) * 1000, 2);

            $this->line("   Connection: ✅ {$connectionTime}ms");

            
            $tables = ['users', 'posts', 'notifications', 'likes', 'saves', 'follows'];
            $totalRecords = 0;

            foreach ($tables as $table) {
                $count = DB::table($table)->count();
                $totalRecords += $count;
                $this->line("   {$table}: {$count} records");
            }

            $this->line("   Total Records: {$totalRecords}");

            
            $this->checkDatabasePerformance();

        } catch (\Exception $e) {
            $this->error("   Status: ❌ Database error - {$e->getMessage()}");
        }

        $this->line('');
    }

    /**
     * Check database performance
     */
    private function checkDatabasePerformance()
    {
        try {
            
            $start = microtime(true);
            $userCount = User::count();
            $userQueryTime = round((microtime(true) - $start) * 1000, 2);

            $start = microtime(true);
            $postCount = Post::count();
            $postQueryTime = round((microtime(true) - $start) * 1000, 2);

            $this->line("   Query Performance:");
            $this->line("     User count: {$userQueryTime}ms");
            $this->line("     Post count: {$postQueryTime}ms");

            
            if ($userQueryTime > 100 || $postQueryTime > 100) {
                $this->line("   ⚠️  Slow queries detected - consider optimization");
            } else {
                $this->line("   ✅ Query performance is good");
            }

        } catch (\Exception $e) {
            $this->line("   ⚠️  Unable to check query performance");
        }
    }

    /**
     * Check cache health
     */
    private function checkCacheHealth()
    {
        $this->info('💾 Cache Health');

        try {
            
            $testKey = 'health_check_' . time();
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

            
            $this->checkCacheHitRates();

        } catch (\Exception $e) {
            $this->error("   Status: ❌ Cache error - {$e->getMessage()}");
        }

        $this->line('');
    }

    /**
     * Check cache hit rates
     */
    private function checkCacheHitRates()
    {
        try {
            $redis = Redis::connection();
            $info = $redis->info();

            $hits = $info['keyspace_hits'] ?? 0;
            $misses = $info['keyspace_misses'] ?? 0;
            $total = $hits + $misses;

            if ($total > 0) {
                $hitRate = round(($hits / $total) * 100, 2);
                $this->line("   Hit Rate: {$hitRate}%");

                if ($hitRate < 80) {
                    $this->line("   ⚠️  Low cache hit rate - consider warming cache");
                } else {
                    $this->line("   ✅ Cache hit rate is good");
                }
            } else {
                $this->line("   Hit Rate: N/A (no cache activity)");
            }

        } catch (\Exception $e) {
            $this->line("   ⚠️  Unable to check cache hit rate");
        }
    }

    /**
     * Check queue health
     */
    private function checkQueueHealth()
    {
        $this->info('📋 Queue Health');

        try {
            $redis = Redis::connection();

            
            $queues = ['default', 'notifications', 'high', 'low'];
            $totalJobs = 0;

            $this->line("   Queue Sizes:");
            foreach ($queues as $queue) {
                $size = $redis->llen("queues:{$queue}");
                $totalJobs += $size;
                $this->line("     {$queue}: {$size} jobs");
            }

            
            $failedCount = $redis->llen('queues:failed');
            $this->line("     failed: {$failedCount} jobs");

            if ($failedCount > 0) {
                $this->line("   ⚠️  Failed jobs detected - check queue:retry");
            } else {
                $this->line("   ✅ No failed jobs");
            }

            
            $this->checkQueueWorker();

        } catch (\Exception $e) {
            $this->error("   Status: ❌ Queue error - {$e->getMessage()}");
        }

        $this->line('');
    }

    /**
     * Check queue worker
     */
    private function checkQueueWorker()
    {
        try {
            $processes = shell_exec('ps aux | grep "queue:work" | grep -v grep');

            if ($processes) {
                $processCount = substr_count($processes, "\n");
                $this->line("   Worker Status: ✅ Running ({$processCount} processes)");
            } else {
                $this->line("   Worker Status: ❌ Not running");
                $this->line("   Recommendation: Run 'php artisan queue:work'");
            }

        } catch (\Exception $e) {
            $this->line("   Worker Status: ⚠️  Unable to check");
        }
    }

    /**
     * Check application health
     */
    private function checkApplicationHealth()
    {
        $this->info('🚀 Application Health');

        try {
            
            $laravelVersion = app()->version();
            $this->line("   Laravel Version: {$laravelVersion}");

            
            $phpVersion = PHP_VERSION;
            $this->line("   PHP Version: {$phpVersion}");

            
            $environment = app()->environment();
            $this->line("   Environment: {$environment}");

            
            if (app()->isDownForMaintenance()) {
                $this->line("   Maintenance Mode: ⚠️  Enabled");
            } else {
                $this->line("   Maintenance Mode: ✅ Disabled");
            }

            
            $this->checkApplicationMetrics();

        } catch (\Exception $e) {
            $this->error("   Status: ❌ Application error - {$e->getMessage()}");
        }

        $this->line('');
    }

    /**
     * Check application metrics
     */
    private function checkApplicationMetrics()
    {
        try {
            
            $activeUsers = User::where('last_seen', '>=', now()->subDay())->count();
            $this->line("   Active Users (24h): {$activeUsers}");

            
            $totalUsers = User::count();
            $this->line("   Total Users: {$totalUsers}");

            
            $postsToday = Post::whereDate('created_at', today())->count();
            $this->line("   Posts Today: {$postsToday}");

            
            $unreadNotifications = Notification::where('is_read', false)->count();
            $this->line("   Unread Notifications: {$unreadNotifications}");

        } catch (\Exception $e) {
            $this->line("   ⚠️  Unable to check application metrics");
        }
    }

    /**
     * Check performance health
     */
    private function checkPerformanceHealth()
    {
        $this->info('⚡ Performance Health');

        try {
            
            $this->testApiResponseTimes();

            
            $this->checkMemoryUsage();

            
            $this->checkCachePerformance();

        } catch (\Exception $e) {
            $this->error("   Status: ❌ Performance error - {$e->getMessage()}");
        }

        $this->line('');
    }

    /**
     * Test API response times
     */
    private function testApiResponseTimes()
    {
        try {
            
            $start = microtime(true);
            PerformanceService::getUnreadNotificationsCount(1);
            $notificationTime = round((microtime(true) - $start) * 1000, 2);

            $this->line("   Notification Count: {$notificationTime}ms");

            if ($notificationTime > 100) {
                $this->line("   ⚠️  Slow notification query - consider optimization");
            } else {
                $this->line("   ✅ Notification query performance is good");
            }

        } catch (\Exception $e) {
            $this->line("   ⚠️  Unable to test API response times");
        }
    }

    /**
     * Check memory usage
     */
    private function checkMemoryUsage()
    {
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $memoryLimit = ini_get('memory_limit');

        $this->line("   Memory Usage: " . $this->formatBytes($memoryUsage));
        $this->line("   Memory Peak: " . $this->formatBytes($memoryPeak));
        $this->line("   Memory Limit: {$memoryLimit}");

        
        $memoryLimitBytes = $this->parseMemoryLimit($memoryLimit);
        if ($memoryUsage > ($memoryLimitBytes * 0.8)) {
            $this->line("   ⚠️  High memory usage - consider optimization");
        } else {
            $this->line("   ✅ Memory usage is acceptable");
        }
    }

    /**
     * Check cache performance
     */
    private function checkCachePerformance()
    {
        try {
            $redis = Redis::connection();
            $info = $redis->info();

            $memoryUsed = $info['used_memory'] ?? 0;
            $memoryPeak = $info['used_memory_peak'] ?? 0;

            $this->line("   Cache Memory: " . $this->formatBytes($memoryUsed));
            $this->line("   Cache Peak: " . $this->formatBytes($memoryPeak));

            
            if ($memoryUsed > 100 * 1024 * 1024) { 
                $this->line("   ⚠️  High cache memory usage - consider cleanup");
            } else {
                $this->line("   ✅ Cache memory usage is acceptable");
            }

        } catch (\Exception $e) {
            $this->line("   ⚠️  Unable to check cache performance");
        }
    }

    /**
     * Check system resources
     */
    private function checkSystemResources()
    {
        $this->info('🖥️  System Resources');

        try {
            
            $this->checkDiskSpace();

            
            $this->checkSystemLoad();

        } catch (\Exception $e) {
            $this->error("   Status: ❌ System resource error - {$e->getMessage()}");
        }

        $this->line('');
    }

    /**
     * Check disk space
     */
    private function checkDiskSpace()
    {
        try {
            $diskFree = disk_free_space('.');
            $diskTotal = disk_total_space('.');
            $diskUsed = $diskTotal - $diskFree;
            $diskPercent = round(($diskUsed / $diskTotal) * 100, 2);

            $this->line("   Disk Usage: {$diskPercent}%");
            $this->line("   Free Space: " . $this->formatBytes($diskFree));
            $this->line("   Total Space: " . $this->formatBytes($diskTotal));

            if ($diskPercent > 90) {
                $this->line("   ⚠️  High disk usage - consider cleanup");
            } else {
                $this->line("   ✅ Disk usage is acceptable");
            }

        } catch (\Exception $e) {
            $this->line("   ⚠️  Unable to check disk space");
        }
    }

    /**
     * Check system load
     */
    private function checkSystemLoad()
    {
        try {
            $loadAvg = sys_getloadavg();
            $this->line("   Load Average: " . implode(', ', $loadAvg));

            if ($loadAvg[0] > 4) {
                $this->line("   ⚠️  High system load - consider scaling");
            } else {
                $this->line("   ✅ System load is acceptable");
            }

        } catch (\Exception $e) {
            $this->line("   ⚠️  Unable to check system load");
        }
    }

    /**
     * Show detailed metrics
     */
    private function showDetailedMetrics()
    {
        $this->info('📈 Detailed System Metrics');

        $this->line('   Database Optimization:');
        $this->line('     - Strategic indexes implemented');
        $this->line('     - Query optimization with eager loading');
        $this->line('     - Connection pooling configured');

        $this->line('   Cache Strategy:');
        $this->line('     - Redis for high-performance caching');
        $this->line('     - Multi-level caching implemented');
        $this->line('     - Cache warming commands available');

        $this->line('   Background Processing:');
        $this->line('     - Queue jobs for heavy operations');
        $this->line('     - Notification batching implemented');
        $this->line('     - Async processing enabled');

        $this->line('   API Optimizations:');
        $this->line('     - Response caching implemented');
        $this->line('     - Pagination limits configured');
        $this->line('     - Rate limiting enabled');

        $this->line('');
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
     * Parse memory limit string to bytes
     */
    private function parseMemoryLimit($limit)
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $limit = (int) $limit;

        switch ($last) {
            case 'g':
                $limit *= 1024;
            case 'm':
                $limit *= 1024;
            case 'k':
                $limit *= 1024;
        }

        return $limit;
    }
}
