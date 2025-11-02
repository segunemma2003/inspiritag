<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseHealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:health-check {--fix : Attempt to fix common issues}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check database health and performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Database Health Check - Inspirtag API');
        $this->line('');

        
        $this->checkConnection();

        
        $this->checkTableStructure();

        
        $this->checkIndexes();

        
        $this->checkForeignKeys();

        
        $this->checkDataIntegrity();

        
        $this->performanceRecommendations();

        $this->line('');
        $this->info('✅ Database health check completed!');
    }

    /**
     * Check database connection
     */
    private function checkConnection()
    {
        $this->info('🔌 Database Connection');

        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $connectionTime = round((microtime(true) - $start) * 1000, 2);

            $this->line("   Connection Time: {$connectionTime}ms");
            $this->line("   Status: ✅ Connected");

        } catch (\Exception $e) {
            $this->error("   Status: ❌ Connection failed - {$e->getMessage()}");
            return false;
        }

        $this->line('');
        return true;
    }

    /**
     * Check table structure
     */
    private function checkTableStructure()
    {
        $this->info('📋 Table Structure');

        $requiredTables = [
            'users', 'posts', 'categories', 'business_accounts',
            'follows', 'likes', 'saves', 'notifications',
            'tags', 'post_tags', 'bookings'
        ];

        $missingTables = [];

        foreach ($requiredTables as $table) {
            if (Schema::hasTable($table)) {
                $this->line("   {$table}: ✅ Exists");
            } else {
                $this->error("   {$table}: ❌ Missing");
                $missingTables[] = $table;
            }
        }

        if (!empty($missingTables)) {
            $this->error("   Missing tables: " . implode(', ', $missingTables));
            if ($this->option('fix')) {
                $this->info("   Run: php artisan migrate");
            }
        }

        $this->line('');
    }

    /**
     * Check database indexes
     */
    private function checkIndexes()
    {
        $this->info('🔍 Database Indexes');

        $requiredIndexes = [
            'users' => ['idx_users_business_admin', 'idx_users_notifications', 'idx_users_last_seen'],
            'posts' => ['idx_posts_user_created', 'idx_posts_category_public_created', 'idx_posts_public_created'],
            'follows' => ['idx_follows_follower', 'idx_follows_following'],
            'likes' => ['idx_likes_user_created', 'idx_likes_post_created'],
            'saves' => ['idx_saves_user_created', 'idx_saves_post_created'],
            'notifications' => ['idx_notifications_user_read_created', 'idx_notifications_user_type_created'],
            'business_accounts' => ['idx_business_verified_bookings', 'idx_business_type_verified'],
            'bookings' => ['idx_bookings_business_status_date', 'idx_bookings_user_status_created'],
            'tags' => ['idx_tags_usage', 'idx_tags_name_usage']
        ];

        foreach ($requiredIndexes as $table => $indexes) {
            if (Schema::hasTable($table)) {
                $this->line("   {$table}:");
                foreach ($indexes as $index) {
                    if ($this->indexExists($table, $index)) {
                        $this->line("     {$index}: ✅ Exists");
                    } else {
                        $this->error("     {$index}: ❌ Missing");
                    }
                }
            }
        }

        $this->line('');
    }

    /**
     * Check if index exists
     */
    private function indexExists($table, $indexName)
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM {$table}");
            foreach ($indexes as $index) {
                if ($index->Key_name === $indexName) {
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check foreign keys
     */
    private function checkForeignKeys()
    {
        $this->info('🔗 Foreign Keys');

        $foreignKeys = [
            'posts' => ['user_id', 'category_id'],
            'follows' => ['follower_id', 'following_id'],
            'likes' => ['user_id', 'post_id'],
            'saves' => ['user_id', 'post_id'],
            'notifications' => ['user_id', 'from_user_id', 'post_id'],
            'business_accounts' => ['user_id'],
            'bookings' => ['user_id', 'business_account_id'],
            'post_tags' => ['post_id', 'tag_id']
        ];

        foreach ($foreignKeys as $table => $keys) {
            if (Schema::hasTable($table)) {
                $this->line("   {$table}:");
                foreach ($keys as $key) {
                    if (Schema::hasColumn($table, $key)) {
                        $this->line("     {$key}: ✅ Exists");
                    } else {
                        $this->error("     {$key}: ❌ Missing");
                    }
                }
            }
        }

        $this->line('');
    }

    /**
     * Check data integrity
     */
    private function checkDataIntegrity()
    {
        $this->info('🔍 Data Integrity');

        try {
            
            $this->checkOrphanedRecords();

            
            $this->checkDuplicateRecords();

            
            $this->checkNullConstraints();

        } catch (\Exception $e) {
            $this->error("   Error: {$e->getMessage()}");
        }

        $this->line('');
    }

    /**
     * Check for orphaned records
     */
    private function checkOrphanedRecords()
    {
        $this->line("   Orphaned Records:");

        
        $orphanedPosts = DB::table('posts')
            ->leftJoin('users', 'posts.user_id', '=', 'users.id')
            ->whereNull('users.id')
            ->count();

        if ($orphanedPosts > 0) {
            $this->error("     Posts without users: {$orphanedPosts}");
        } else {
            $this->line("     Posts: ✅ No orphaned records");
        }

        
        $orphanedLikes = DB::table('likes')
            ->leftJoin('users', 'likes.user_id', '=', 'users.id')
            ->whereNull('users.id')
            ->count();

        if ($orphanedLikes > 0) {
            $this->error("     Likes without users: {$orphanedLikes}");
        } else {
            $this->line("     Likes: ✅ No orphaned records");
        }
    }

    /**
     * Check for duplicate records
     */
    private function checkDuplicateRecords()
    {
        $this->line("   Duplicate Records:");

        
        $duplicateFollows = DB::table('follows')
            ->select('follower_id', 'following_id')
            ->groupBy('follower_id', 'following_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        if ($duplicateFollows > 0) {
            $this->error("     Duplicate follows: {$duplicateFollows}");
        } else {
            $this->line("     Follows: ✅ No duplicates");
        }

        
        $duplicateLikes = DB::table('likes')
            ->select('user_id', 'post_id')
            ->groupBy('user_id', 'post_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        if ($duplicateLikes > 0) {
            $this->error("     Duplicate likes: {$duplicateLikes}");
        } else {
            $this->line("     Likes: ✅ No duplicates");
        }
    }

    /**
     * Check null constraints
     */
    private function checkNullConstraints()
    {
        $this->line("   Null Constraints:");

        
        $usersWithoutEmail = DB::table('users')->whereNull('email')->count();
        if ($usersWithoutEmail > 0) {
            $this->error("     Users without email: {$usersWithoutEmail}");
        } else {
            $this->line("     Users email: ✅ All have email");
        }

        
        $postsWithoutMedia = DB::table('posts')->whereNull('media_url')->count();
        if ($postsWithoutMedia > 0) {
            $this->error("     Posts without media: {$postsWithoutMedia}");
        } else {
            $this->line("     Posts media: ✅ All have media");
        }
    }

    /**
     * Performance recommendations
     */
    private function performanceRecommendations()
    {
        $this->info('💡 Performance Recommendations');

        $this->line("   Database Optimization:");
        $this->line("     - Ensure all indexes are created");
        $this->line("     - Monitor slow query log");
        $this->line("     - Consider read replicas for heavy read operations");

        $this->line("   Application Optimization:");
        $this->line("     - Use eager loading to prevent N+1 queries");
        $this->line("     - Implement query result caching");
        $this->line("     - Use database connection pooling");

        $this->line("   Monitoring:");
        $this->line("     - Set up database performance monitoring");
        $this->line("     - Monitor connection pool usage");
        $this->line("     - Track query execution times");

        $this->line('');
    }
}
