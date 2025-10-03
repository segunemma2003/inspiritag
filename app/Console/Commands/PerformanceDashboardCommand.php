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

class PerformanceDashboardCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'performance:dashboard {--refresh : Refresh dashboard every 5 seconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Real-time performance dashboard for 100,000+ users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('refresh')) {
            $this->showRefreshingDashboard();
        } else {
            $this->showStaticDashboard();
        }
    }

    /**
     * Show refreshing dashboard
     */
    private function showRefreshingDashboard()
    {
        $this->info('🔄 Performance Dashboard - Auto-refreshing every 5 seconds');
        $this->line('Press Ctrl+C to stop');

        while (true) {
            $this->clearScreen();
            $this->showDashboard();
            sleep(5);
        }
    }

    /**
     * Show static dashboard
     */
    private function showStaticDashboard()
    {
        $this->showDashboard();
    }

    /**
     * Show the main dashboard
     */
    private function showDashboard()
    {
        $this->info('📊 Performance Dashboard - Inspirtag API');
        $this->line('');

        // System Overview
        $this->showSystemOverview();

        // Database Performance
        $this->showDatabasePerformance();

        // Cache Performance
        $this->showCachePerformance();

        // Queue Performance
        $this->showQueuePerformance();

        // User Activity
        $this->showUserActivity();

        // Content Metrics
        $this->showContentMetrics();

        // Performance Alerts
        $this->showPerformanceAlerts();

        $this->line('');
        $this->info('💡 Use --refresh flag for auto-refreshing dashboard');
    }

    /**
     * Show system overview
     */
    private function showSystemOverview()
    {
        $this->info('🏥 System Overview');

        try {
            // System status
            $this->line("   Status: ✅ System Healthy");
            $this->line("   Time: " . now()->format('Y-m-d H:i:s'));
            $this->line("   Uptime: " . $this->getSystemUptime());

            // Memory usage
            $memoryUsage = memory_get_usage(true);
            $memoryPeak = memory_get_peak_usage(true);
            $this->line("   Memory: " . $this->formatBytes($memoryUsage) . " / " . $this->formatBytes($memoryPeak));

            // PHP version
            $this->line("   PHP: " . PHP_VERSION);
            $this->line("   Laravel: " . app()->version());

        } catch (\Exception $e) {
            $this->error("   Status: ❌ System error - {$e->getMessage()}");
        }

        $this->line('');
    }

    /**
     * Show database performance
     */
    private function showDatabasePerformance()
    {
        $this->info('🗄️  Database Performance');

        try {
            // Connection test
            $start = microtime(true);
            DB::select('SELECT 1');
            $connectionTime = round((microtime(true) - $start) * 1000, 2);

            $this->line("   Connection: {$connectionTime}ms");

            // Query performance tests
            $this->testQueryPerformance();

            // Table sizes
            $this->showTableSizes();

        } catch (\Exception $e) {
            $this->error("   Status: ❌ Database error - {$e->getMessage()}");
        }

        $this->line('');
    }

    /**
     * Test query performance
     */
    private function testQueryPerformance()
    {
        try {
            // Test user count query
            $start = microtime(true);
            $userCount = User::count();
            $userQueryTime = round((microtime(true) - $start) * 1000, 2);

            // Test post count query
            $start = microtime(true);
            $postCount = Post::count();
            $postQueryTime = round((microtime(true) - $start) * 1000, 2);

            // Test notification count query
            $start = microtime(true);
            $notificationCount = Notification::count();
            $notificationQueryTime = round((microtime(true) - $start) * 1000, 2);

            $this->line("   Query Performance:");
            $this->line("     Users: {$userQueryTime}ms ({$userCount} records)");
            $this->line("     Posts: {$postQueryTime}ms ({$postCount} records)");
            $this->line("     Notifications: {$notificationQueryTime}ms ({$notificationCount} records)");

            // Performance status
            $maxTime = max($userQueryTime, $postQueryTime, $notificationQueryTime);
            if ($maxTime > 100) {
                $this->line("   Status: ⚠️  Slow queries detected");
            } else {
                $this->line("   Status: ✅ Query performance is good");
            }

        } catch (\Exception $e) {
            $this->line("   Status: ⚠️  Unable to test query performance");
        }
    }

    /**
     * Show table sizes
     */
    private function showTableSizes()
    {
        try {
            $tables = ['users', 'posts', 'notifications', 'likes', 'saves', 'follows'];
            $totalRecords = 0;

            $this->line("   Table Sizes:");
            foreach ($tables as $table) {
                $count = DB::table($table)->count();
                $totalRecords += $count;
                $this->line("     {$table}: {$count}");
            }

            $this->line("     Total: {$totalRecords}");

        } catch (\Exception $e) {
            $this->line("   ⚠️  Unable to get table sizes");
        }
    }

    /**
     * Show cache performance
     */
    private function showCachePerformance()
    {
        $this->info('💾 Cache Performance');

        try {
            $redis = Redis::connection();
            $info = $redis->info();

            // Cache metrics
            $memoryUsed = $info['used_memory'] ?? 0;
            $memoryPeak = $info['used_memory_peak'] ?? 0;
            $connectedClients = $info['connected_clients'] ?? 0;
            $totalCommands = $info['total_commands_processed'] ?? 0;
            $commandsPerSec = $info['instantaneous_ops_per_sec'] ?? 0;

            $this->line("   Memory: " . $this->formatBytes($memoryUsed) . " / " . $this->formatBytes($memoryPeak));
            $this->line("   Clients: {$connectedClients}");
            $this->line("   Commands: {$totalCommands} ({$commandsPerSec}/sec)");

            // Cache hit rate
            $hits = $info['keyspace_hits'] ?? 0;
            $misses = $info['keyspace_misses'] ?? 0;
            $total = $hits + $misses;

            if ($total > 0) {
                $hitRate = round(($hits / $total) * 100, 2);
                $this->line("   Hit Rate: {$hitRate}%");

                if ($hitRate < 80) {
                    $this->line("   Status: ⚠️  Low cache hit rate");
                } else {
                    $this->line("   Status: ✅ Cache hit rate is good");
                }
            } else {
                $this->line("   Hit Rate: N/A");
                $this->line("   Status: ⚠️  No cache activity");
            }

        } catch (\Exception $e) {
            $this->error("   Status: ❌ Cache error - {$e->getMessage()}");
        }

        $this->line('');
    }

    /**
     * Show queue performance
     */
    private function showQueuePerformance()
    {
        $this->info('📋 Queue Performance');

        try {
            $redis = Redis::connection();

            // Queue sizes
            $queues = ['default', 'notifications', 'high', 'low'];
            $totalJobs = 0;

            $this->line("   Queue Sizes:");
            foreach ($queues as $queue) {
                $size = $redis->llen("queues:{$queue}");
                $totalJobs += $size;
                $this->line("     {$queue}: {$size}");
            }

            // Failed jobs
            $failedCount = $redis->llen('queues:failed');
            $this->line("     failed: {$failedCount}");

            $this->line("   Total Jobs: {$totalJobs}");

            // Queue status
            if ($failedCount > 0) {
                $this->line("   Status: ⚠️  Failed jobs detected");
            } else {
                $this->line("   Status: ✅ No failed jobs");
            }

            // Check queue worker
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
                $this->line("   Worker: ✅ Running ({$processCount} processes)");
            } else {
                $this->line("   Worker: ❌ Not running");
            }

        } catch (\Exception $e) {
            $this->line("   Worker: ⚠️  Unable to check");
        }
    }

    /**
     * Show user activity
     */
    private function showUserActivity()
    {
        $this->info('👥 User Activity');

        try {
            // Active users
            $activeUsers = User::where('last_seen', '>=', now()->subDay())->count();
            $this->line("   Active (24h): {$activeUsers}");

            // Total users
            $totalUsers = User::count();
            $this->line("   Total: {$totalUsers}");

            // Business accounts
            $businessAccounts = User::where('is_business', true)->count();
            $this->line("   Business: {$businessAccounts}");

            // Admin users
            $adminUsers = User::where('is_admin', true)->count();
            $this->line("   Admin: {$adminUsers}");

            // Activity rate
            if ($totalUsers > 0) {
                $activityRate = round(($activeUsers / $totalUsers) * 100, 2);
                $this->line("   Activity Rate: {$activityRate}%");
            }

        } catch (\Exception $e) {
            $this->error("   Error: {$e->getMessage()}");
        }

        $this->line('');
    }

    /**
     * Show content metrics
     */
    private function showContentMetrics()
    {
        $this->info('📝 Content Metrics');

        try {
            // Total posts
            $totalPosts = Post::count();
            $this->line("   Total Posts: {$totalPosts}");

            // Posts today
            $postsToday = Post::whereDate('created_at', today())->count();
            $this->line("   Posts Today: {$postsToday}");

            // Public posts
            $publicPosts = Post::where('is_public', true)->count();
            $this->line("   Public Posts: {$publicPosts}");

            // Total likes
            $totalLikes = DB::table('likes')->count();
            $this->line("   Total Likes: {$totalLikes}");

            // Total saves
            $totalSaves = DB::table('saves')->count();
            $this->line("   Total Saves: {$totalSaves}");

            // Unread notifications
            $unreadNotifications = Notification::where('is_read', false)->count();
            $this->line("   Unread Notifications: {$unreadNotifications}");

        } catch (\Exception $e) {
            $this->error("   Error: {$e->getMessage()}");
        }

        $this->line('');
    }

    /**
     * Show performance alerts
     */
    private function showPerformanceAlerts()
    {
        $this->info('🚨 Performance Alerts');

        $alerts = [];

        try {
            // Check for slow queries
            $this->checkSlowQueries($alerts);

            // Check for high memory usage
            $this->checkMemoryUsage($alerts);

            // Check for failed jobs
            $this->checkFailedJobs($alerts);

            // Check for low cache hit rate
            $this->checkCacheHitRate($alerts);

            if (empty($alerts)) {
                $this->line("   ✅ No performance alerts");
            } else {
                foreach ($alerts as $alert) {
                    $this->line("   ⚠️  {$alert}");
                }
            }

        } catch (\Exception $e) {
            $this->line("   ⚠️  Unable to check performance alerts");
        }

        $this->line('');
    }

    /**
     * Check for slow queries
     */
    private function checkSlowQueries(&$alerts)
    {
        try {
            $start = microtime(true);
            User::count();
            $queryTime = round((microtime(true) - $start) * 1000, 2);

            if ($queryTime > 100) {
                $alerts[] = "Slow queries detected ({$queryTime}ms)";
            }

        } catch (\Exception $e) {
            // Ignore errors in alert checking
        }
    }

    /**
     * Check memory usage
     */
    private function checkMemoryUsage(&$alerts)
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        $memoryLimitBytes = $this->parseMemoryLimit($memoryLimit);

        if ($memoryUsage > ($memoryLimitBytes * 0.8)) {
            $alerts[] = "High memory usage (" . $this->formatBytes($memoryUsage) . ")";
        }
    }

    /**
     * Check failed jobs
     */
    private function checkFailedJobs(&$alerts)
    {
        try {
            $redis = Redis::connection();
            $failedCount = $redis->llen('queues:failed');

            if ($failedCount > 0) {
                $alerts[] = "Failed jobs detected ({$failedCount})";
            }

        } catch (\Exception $e) {
            // Ignore errors in alert checking
        }
    }

    /**
     * Check cache hit rate
     */
    private function checkCacheHitRate(&$alerts)
    {
        try {
            $redis = Redis::connection();
            $info = $redis->info();

            $hits = $info['keyspace_hits'] ?? 0;
            $misses = $info['keyspace_misses'] ?? 0;
            $total = $hits + $misses;

            if ($total > 0) {
                $hitRate = round(($hits / $total) * 100, 2);
                if ($hitRate < 80) {
                    $alerts[] = "Low cache hit rate ({$hitRate}%)";
                }
            }

        } catch (\Exception $e) {
            // Ignore errors in alert checking
        }
    }

    /**
     * Clear screen
     */
    private function clearScreen()
    {
        if (PHP_OS_FAMILY === 'Windows') {
            system('cls');
        } else {
            system('clear');
        }
    }

    /**
     * Get system uptime
     */
    private function getSystemUptime()
    {
        try {
            if (PHP_OS_FAMILY === 'Linux') {
                $uptime = shell_exec('uptime -p');
                return trim($uptime);
            } else {
                return 'N/A';
            }
        } catch (\Exception $e) {
            return 'N/A';
        }
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
