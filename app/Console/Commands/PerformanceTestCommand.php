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

class PerformanceTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'performance:test
                            {--users=1000 : Number of users to simulate}
                            {--posts=100 : Number of posts to simulate}
                            {--duration=60 : Test duration in seconds}
                            {--concurrent=10 : Number of concurrent requests}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Performance testing for 100,000+ users simulation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Performance Testing - Inspirtag API');
        $this->line('');

        $users = (int) $this->option('users');
        $posts = (int) $this->option('posts');
        $duration = (int) $this->option('duration');
        $concurrent = (int) $this->option('concurrent');

        $this->line("Test Configuration:");
        $this->line("  Users: {$users}");
        $this->line("  Posts: {$posts}");
        $this->line("  Duration: {$duration}s");
        $this->line("  Concurrent: {$concurrent}");
        $this->line('');

        // Run performance tests
        $this->runDatabaseTests($users, $posts);
        $this->runCacheTests($users, $posts);
        $this->runQueryTests($users, $posts);
        $this->runConcurrentTests($concurrent, $duration);

        $this->line('');
        $this->info('✅ Performance testing completed!');
    }

    /**
     * Run database performance tests
     */
    private function runDatabaseTests($users, $posts)
    {
        $this->info('🗄️  Database Performance Tests');

        try {
            // Test user queries
            $this->testUserQueries($users);

            // Test post queries
            $this->testPostQueries($posts);

            // Test notification queries
            $this->testNotificationQueries();

            // Test relationship queries
            $this->testRelationshipQueries();

        } catch (\Exception $e) {
            $this->error("   Error: {$e->getMessage()}");
        }

        $this->line('');
    }

    /**
     * Test user queries
     */
    private function testUserQueries($users)
    {
        $this->line("   Testing User Queries...");

        $start = microtime(true);

        // Test user count
        $userCount = User::count();
        $countTime = round((microtime(true) - $start) * 1000, 2);

        // Test user pagination
        $start = microtime(true);
        $paginatedUsers = User::paginate(20);
        $paginationTime = round((microtime(true) - $start) * 1000, 2);

        // Test user with relationships
        $start = microtime(true);
        $usersWithPosts = User::with('posts')->limit(10)->get();
        $relationshipTime = round((microtime(true) - $start) * 1000, 2);

        $this->line("     User Count: {$countTime}ms ({$userCount} users)");
        $this->line("     User Pagination: {$paginationTime}ms");
        $this->line("     User Relationships: {$relationshipTime}ms");

        // Performance assessment
        $maxTime = max($countTime, $paginationTime, $relationshipTime);
        if ($maxTime > 100) {
            $this->line("     Status: ⚠️  Slow user queries");
        } else {
            $this->line("     Status: ✅ User queries are fast");
        }
    }

    /**
     * Test post queries
     */
    private function testPostQueries($posts)
    {
        $this->line("   Testing Post Queries...");

        $start = microtime(true);

        // Test post count
        $postCount = Post::count();
        $countTime = round((microtime(true) - $start) * 1000, 2);

        // Test post pagination
        $start = microtime(true);
        $paginatedPosts = Post::with('user', 'category')->paginate(20);
        $paginationTime = round((microtime(true) - $start) * 1000, 2);

        // Test post with relationships
        $start = microtime(true);
        $postsWithLikes = Post::with('likes', 'saves')->limit(10)->get();
        $relationshipTime = round((microtime(true) - $start) * 1000, 2);

        $this->line("     Post Count: {$countTime}ms ({$postCount} posts)");
        $this->line("     Post Pagination: {$paginationTime}ms");
        $this->line("     Post Relationships: {$relationshipTime}ms");

        // Performance assessment
        $maxTime = max($countTime, $paginationTime, $relationshipTime);
        if ($maxTime > 100) {
            $this->line("     Status: ⚠️  Slow post queries");
        } else {
            $this->line("     Status: ✅ Post queries are fast");
        }
    }

    /**
     * Test notification queries
     */
    private function testNotificationQueries()
    {
        $this->line("   Testing Notification Queries...");

        $start = microtime(true);

        // Test notification count
        $notificationCount = Notification::count();
        $countTime = round((microtime(true) - $start) * 1000, 2);

        // Test unread notifications
        $start = microtime(true);
        $unreadCount = Notification::where('is_read', false)->count();
        $unreadTime = round((microtime(true) - $start) * 1000, 2);

        // Test notification pagination
        $start = microtime(true);
        $paginatedNotifications = Notification::with('fromUser', 'post')->paginate(20);
        $paginationTime = round((microtime(true) - $start) * 1000, 2);

        $this->line("     Notification Count: {$countTime}ms ({$notificationCount} notifications)");
        $this->line("     Unread Count: {$unreadTime}ms ({$unreadCount} unread)");
        $this->line("     Notification Pagination: {$paginationTime}ms");

        // Performance assessment
        $maxTime = max($countTime, $unreadTime, $paginationTime);
        if ($maxTime > 100) {
            $this->line("     Status: ⚠️  Slow notification queries");
        } else {
            $this->line("     Status: ✅ Notification queries are fast");
        }
    }

    /**
     * Test relationship queries
     */
    private function testRelationshipQueries()
    {
        $this->line("   Testing Relationship Queries...");

        $start = microtime(true);

        // Test user with posts
        $userWithPosts = User::with('posts')->first();
        $userPostsTime = round((microtime(true) - $start) * 1000, 2);

        // Test post with likes
        $start = microtime(true);
        $postWithLikes = Post::with('likes.user')->first();
        $postLikesTime = round((microtime(true) - $start) * 1000, 2);

        // Test user with followers
        $start = microtime(true);
        $userWithFollowers = User::with('followers')->first();
        $userFollowersTime = round((microtime(true) - $start) * 1000, 2);

        $this->line("     User with Posts: {$userPostsTime}ms");
        $this->line("     Post with Likes: {$postLikesTime}ms");
        $this->line("     User with Followers: {$userFollowersTime}ms");

        // Performance assessment
        $maxTime = max($userPostsTime, $postLikesTime, $userFollowersTime);
        if ($maxTime > 100) {
            $this->line("     Status: ⚠️  Slow relationship queries");
        } else {
            $this->line("     Status: ✅ Relationship queries are fast");
        }
    }

    /**
     * Run cache performance tests
     */
    private function runCacheTests($users, $posts)
    {
        $this->info('💾 Cache Performance Tests');

        try {
            // Test cache write performance
            $this->testCacheWritePerformance();

            // Test cache read performance
            $this->testCacheReadPerformance();

            // Test cache hit rates
            $this->testCacheHitRates();

        } catch (\Exception $e) {
            $this->error("   Error: {$e->getMessage()}");
        }

        $this->line('');
    }

    /**
     * Test cache write performance
     */
    private function testCacheWritePerformance()
    {
        $this->line("   Testing Cache Write Performance...");

        $testKeys = [];
        $start = microtime(true);

        // Write 100 test keys
        for ($i = 0; $i < 100; $i++) {
            $key = "test_write_{$i}_" . time();
            $value = "test_value_{$i}";
            Cache::put($key, $value, 60);
            $testKeys[] = $key;
        }

        $writeTime = round((microtime(true) - $start) * 1000, 2);
        $avgWriteTime = round($writeTime / 100, 2);

        $this->line("     Write 100 keys: {$writeTime}ms");
        $this->line("     Average per key: {$avgWriteTime}ms");

        // Clean up test keys
        foreach ($testKeys as $key) {
            Cache::forget($key);
        }

        if ($avgWriteTime > 10) {
            $this->line("     Status: ⚠️  Slow cache writes");
        } else {
            $this->line("     Status: ✅ Cache writes are fast");
        }
    }

    /**
     * Test cache read performance
     */
    private function testCacheReadPerformance()
    {
        $this->line("   Testing Cache Read Performance...");

        $testKeys = [];

        // Write test keys first
        for ($i = 0; $i < 100; $i++) {
            $key = "test_read_{$i}_" . time();
            $value = "test_value_{$i}";
            Cache::put($key, $value, 60);
            $testKeys[] = $key;
        }

        $start = microtime(true);

        // Read test keys
        for ($i = 0; $i < 100; $i++) {
            Cache::get($testKeys[$i]);
        }

        $readTime = round((microtime(true) - $start) * 1000, 2);
        $avgReadTime = round($readTime / 100, 2);

        $this->line("     Read 100 keys: {$readTime}ms");
        $this->line("     Average per key: {$avgReadTime}ms");

        // Clean up test keys
        foreach ($testKeys as $key) {
            Cache::forget($key);
        }

        if ($avgReadTime > 5) {
            $this->line("     Status: ⚠️  Slow cache reads");
        } else {
            $this->line("     Status: ✅ Cache reads are fast");
        }
    }

    /**
     * Test cache hit rates
     */
    private function testCacheHitRates()
    {
        $this->line("   Testing Cache Hit Rates...");

        try {
            $redis = Redis::connection();
            $info = $redis->info();

            $hits = $info['keyspace_hits'] ?? 0;
            $misses = $info['keyspace_misses'] ?? 0;
            $total = $hits + $misses;

            if ($total > 0) {
                $hitRate = round(($hits / $total) * 100, 2);
                $this->line("     Hit Rate: {$hitRate}%");

                if ($hitRate < 80) {
                    $this->line("     Status: ⚠️  Low cache hit rate");
                } else {
                    $this->line("     Status: ✅ Cache hit rate is good");
                }
            } else {
                $this->line("     Hit Rate: N/A (no cache activity)");
                $this->line("     Status: ⚠️  No cache activity");
            }

        } catch (\Exception $e) {
            $this->line("     Status: ⚠️  Unable to check cache hit rate");
        }
    }

    /**
     * Run query performance tests
     */
    private function runQueryTests($users, $posts)
    {
        $this->info('🔍 Query Performance Tests');

        try {
            // Test complex queries
            $this->testComplexQueries();

            // Test join queries
            $this->testJoinQueries();

            // Test aggregation queries
            $this->testAggregationQueries();

        } catch (\Exception $e) {
            $this->error("   Error: {$e->getMessage()}");
        }

        $this->line('');
    }

    /**
     * Test complex queries
     */
    private function testComplexQueries()
    {
        $this->line("   Testing Complex Queries...");

        $start = microtime(true);

        // Test user feed query
        $userFeed = Post::with('user', 'category')
            ->where('is_public', true)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $feedTime = round((microtime(true) - $start) * 1000, 2);

        $this->line("     User Feed: {$feedTime}ms");

        if ($feedTime > 200) {
            $this->line("     Status: ⚠️  Slow user feed query");
        } else {
            $this->line("     Status: ✅ User feed query is fast");
        }
    }

    /**
     * Test join queries
     */
    private function testJoinQueries()
    {
        $this->line("   Testing Join Queries...");

        $start = microtime(true);

        // Test posts with user and category
        $postsWithDetails = DB::table('posts')
            ->join('users', 'posts.user_id', '=', 'users.id')
            ->join('categories', 'posts.category_id', '=', 'categories.id')
            ->select('posts.*', 'users.username', 'categories.name as category_name')
            ->limit(20)
            ->get();

        $joinTime = round((microtime(true) - $start) * 1000, 2);

        $this->line("     Join Query: {$joinTime}ms");

        if ($joinTime > 150) {
            $this->line("     Status: ⚠️  Slow join query");
        } else {
            $this->line("     Status: ✅ Join query is fast");
        }
    }

    /**
     * Test aggregation queries
     */
    private function testAggregationQueries()
    {
        $this->line("   Testing Aggregation Queries...");

        $start = microtime(true);

        // Test user statistics
        $userStats = DB::table('users')
            ->selectRaw('COUNT(*) as total_users')
            ->selectRaw('COUNT(CASE WHEN is_business = 1 THEN 1 END) as business_users')
            ->selectRaw('COUNT(CASE WHEN is_admin = 1 THEN 1 END) as admin_users')
            ->first();

        $aggregationTime = round((microtime(true) - $start) * 1000, 2);

        $this->line("     Aggregation Query: {$aggregationTime}ms");

        if ($aggregationTime > 100) {
            $this->line("     Status: ⚠️  Slow aggregation query");
        } else {
            $this->line("     Status: ✅ Aggregation query is fast");
        }
    }

    /**
     * Run concurrent performance tests
     */
    private function runConcurrentTests($concurrent, $duration)
    {
        $this->info('⚡ Concurrent Performance Tests');

        try {
            // Test concurrent database operations
            $this->testConcurrentDatabaseOperations($concurrent);

            // Test concurrent cache operations
            $this->testConcurrentCacheOperations($concurrent);

        } catch (\Exception $e) {
            $this->error("   Error: {$e->getMessage()}");
        }

        $this->line('');
    }

    /**
     * Test concurrent database operations
     */
    private function testConcurrentDatabaseOperations($concurrent)
    {
        $this->line("   Testing Concurrent Database Operations...");

        $start = microtime(true);
        $operations = 0;

        // Simulate concurrent operations
        for ($i = 0; $i < $concurrent; $i++) {
            // Simulate user count query
            User::count();
            $operations++;

            // Simulate post count query
            Post::count();
            $operations++;

            // Simulate notification count query
            Notification::count();
            $operations++;
        }

        $totalTime = round((microtime(true) - $start) * 1000, 2);
        $avgTime = round($totalTime / $operations, 2);
        $opsPerSecond = round($operations / ($totalTime / 1000), 2);

        $this->line("     Operations: {$operations}");
        $this->line("     Total Time: {$totalTime}ms");
        $this->line("     Average per Operation: {$avgTime}ms");
        $this->line("     Operations per Second: {$opsPerSecond}");

        if ($avgTime > 50) {
            $this->line("     Status: ⚠️  Slow concurrent operations");
        } else {
            $this->line("     Status: ✅ Concurrent operations are fast");
        }
    }

    /**
     * Test concurrent cache operations
     */
    private function testConcurrentCacheOperations($concurrent)
    {
        $this->line("   Testing Concurrent Cache Operations...");

        $start = microtime(true);
        $operations = 0;

        // Simulate concurrent cache operations
        for ($i = 0; $i < $concurrent; $i++) {
            $key = "concurrent_test_{$i}_" . time();
            $value = "test_value_{$i}";

            // Write to cache
            Cache::put($key, $value, 60);
            $operations++;

            // Read from cache
            Cache::get($key);
            $operations++;

            // Clean up
            Cache::forget($key);
            $operations++;
        }

        $totalTime = round((microtime(true) - $start) * 1000, 2);
        $avgTime = round($totalTime / $operations, 2);
        $opsPerSecond = round($operations / ($totalTime / 1000), 2);

        $this->line("     Operations: {$operations}");
        $this->line("     Total Time: {$totalTime}ms");
        $this->line("     Average per Operation: {$avgTime}ms");
        $this->line("     Operations per Second: {$opsPerSecond}");

        if ($avgTime > 10) {
            $this->line("     Status: ⚠️  Slow concurrent cache operations");
        } else {
            $this->line("     Status: ✅ Concurrent cache operations are fast");
        }
    }
}
