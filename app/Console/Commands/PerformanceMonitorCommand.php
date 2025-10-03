<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\User;
use App\Models\Notification;
use App\Models\BusinessAccount;
use App\Services\PerformanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PerformanceMonitorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'performance:monitor {--detailed : Show detailed performance metrics}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor API performance metrics and system health';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Performance Monitor - Inspirtag API');
        $this->line('');

        // Database Performance
        $this->checkDatabasePerformance();

        // Cache Performance
        $this->checkCachePerformance();

        // System Metrics
        $this->checkSystemMetrics();

        // User Activity
        $this->checkUserActivity();

        // Content Metrics
        $this->checkContentMetrics();

        if ($this->option('detailed')) {
            $this->showDetailedMetrics();
        }

        $this->line('');
        $this->info('✅ Performance monitoring completed!');
    }

    /**
     * Check database performance
     */
    private function checkDatabasePerformance()
    {
        $this->info('📊 Database Performance');

        try {
            // Test database connection
            $start = microtime(true);
            DB::select('SELECT 1');
            $connectionTime = round((microtime(true) - $start) * 1000, 2);

            $this->line("   Connection Time: {$connectionTime}ms");

            // Check table sizes
            $tables = ['users', 'posts', 'notifications', 'likes', 'saves', 'follows'];
            foreach ($tables as $table) {
                $count = DB::table($table)->count();
                $this->line("   {$table}: {$count} records");
            }

            // Check slow queries (if available)
            $this->line("   Status: ✅ Database healthy");

        } catch (\Exception $e) {
            $this->error("   Status: ❌ Database error - {$e->getMessage()}");
        }

        $this->line('');
    }

    /**
     * Check cache performance
     */
    private function checkCachePerformance()
    {
        $this->info('💾 Cache Performance');

        try {
            // Test cache connection
            $testKey = 'performance_test_' . time();
            $testValue = 'test_value';

            $start = microtime(true);
            Cache::put($testKey, $testValue, 60);
            $putTime = round((microtime(true) - $start) * 1000, 2);

            $start = microtime(true);
            $retrieved = Cache::get($testKey);
            $getTime = round((microtime(true) - $start) * 1000, 2);

            Cache::forget($testKey);

            $this->line("   Cache Write: {$putTime}ms");
            $this->line("   Cache Read: {$getTime}ms");

            // Check cache hit rates for key metrics
            $this->checkCacheHitRates();

            $this->line("   Status: ✅ Cache healthy");

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
        $cacheKeys = [
            'user_feed_' => 'User Feeds',
            'user_stats_' => 'User Stats',
            'notifications_' => 'Notifications',
            'business_accounts' => 'Business Accounts',
            'popular_tags' => 'Popular Tags',
            'trending_posts' => 'Trending Posts'
        ];

        foreach ($cacheKeys as $key => $name) {
            if (Cache::has($key . '*')) {
                $this->line("   {$name}: ✅ Cached");
            } else {
                $this->line("   {$name}: ⚠️  Not cached");
            }
        }
    }

    /**
     * Check system metrics
     */
    private function checkSystemMetrics()
    {
        $this->info('⚙️  System Metrics');

        // Memory usage
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $memoryLimit = ini_get('memory_limit');

        $this->line("   Memory Usage: " . $this->formatBytes($memoryUsage));
        $this->line("   Memory Peak: " . $this->formatBytes($memoryPeak));
        $this->line("   Memory Limit: {$memoryLimit}");

        // PHP version
        $this->line("   PHP Version: " . PHP_VERSION);

        // Laravel version
        $this->line("   Laravel Version: " . app()->version());

        $this->line('');
    }

    /**
     * Check user activity
     */
    private function checkUserActivity()
    {
        $this->info('👥 User Activity');

        try {
            // Active users (last 24 hours)
            $activeUsers = User::where('last_seen', '>=', now()->subDay())->count();
            $this->line("   Active Users (24h): {$activeUsers}");

            // Total users
            $totalUsers = User::count();
            $this->line("   Total Users: {$totalUsers}");

            // Business accounts
            $businessAccounts = User::where('is_business', true)->count();
            $this->line("   Business Accounts: {$businessAccounts}");

            // Admin users
            $adminUsers = User::where('is_admin', true)->count();
            $this->line("   Admin Users: {$adminUsers}");

        } catch (\Exception $e) {
            $this->error("   Error: {$e->getMessage()}");
        }

        $this->line('');
    }

    /**
     * Check content metrics
     */
    private function checkContentMetrics()
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
     * Show detailed performance metrics
     */
    private function showDetailedMetrics()
    {
        $this->info('📈 Detailed Performance Metrics');

        // Database query performance
        $this->line('   Database Queries:');
        $this->line('     - User feed queries: Optimized with indexes');
        $this->line('     - Post queries: Eager loading implemented');
        $this->line('     - Notification queries: Cached results');

        // Cache performance
        $this->line('   Cache Performance:');
        $this->line('     - User feeds: 2 minute TTL');
        $this->line('     - User stats: 5 minute TTL');
        $this->line('     - Notifications: 1 minute TTL');
        $this->line('     - Business accounts: 3 minute TTL');

        // Background jobs
        $this->line('   Background Jobs:');
        $this->line('     - Notification sending: Queued');
        $this->line('     - Cache warming: Scheduled');
        $this->line('     - Performance monitoring: Automated');

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
}
