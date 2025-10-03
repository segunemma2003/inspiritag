<?php

namespace App\Console\Commands;

use App\Jobs\SendNotificationJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

class QueueManagementCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:manage
                            {action : The action to perform (status|clear|monitor|stats|test)}
                            {--queue=default : Queue name for operations}
                            {--force : Force operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage application queues (status, clear, monitor, stats, test)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'status':
                $this->showQueueStatus();
                break;
            case 'clear':
                $this->clearQueue();
                break;
            case 'monitor':
                $this->monitorQueue();
                break;
            case 'stats':
                $this->showQueueStats();
                break;
            case 'test':
                $this->testQueue();
                break;
            default:
                $this->error("Unknown action: {$action}");
                $this->line("Available actions: status, clear, monitor, stats, test");
                return 1;
        }

        return 0;
    }

    /**
     * Show queue status
     */
    private function showQueueStatus()
    {
        $this->info('📋 Queue Status - Inspirtag API');
        $this->line('');

        try {
            $queue = $this->option('queue');
            $this->line("   Queue: {$queue}");

            // Check if queue is running
            $this->checkQueueRunning();

            // Show queue sizes
            $this->showQueueSizes();

            // Show failed jobs
            $this->showFailedJobs();

        } catch (\Exception $e) {
            $this->error("   Error: ❌ Queue error - {$e->getMessage()}");
        }

        $this->line('');
    }

    /**
     * Check if queue is running
     */
    private function checkQueueRunning()
    {
        try {
            // Check if queue worker is running
            $processes = shell_exec('ps aux | grep "queue:work" | grep -v grep');

            if ($processes) {
                $this->line("   Worker Status: ✅ Running");
                $this->line("   Processes: " . substr_count($processes, "\n"));
            } else {
                $this->line("   Worker Status: ❌ Not running");
                $this->line("   Recommendation: Run 'php artisan queue:work'");
            }

        } catch (\Exception $e) {
            $this->line("   Worker Status: ⚠️  Unable to check");
        }
    }

    /**
     * Show queue sizes
     */
    private function showQueueSizes()
    {
        try {
            $redis = Redis::connection();

            // Get queue sizes
            $queues = ['default', 'notifications', 'high', 'low'];

            $this->line("   Queue Sizes:");
            foreach ($queues as $queue) {
                $size = $redis->llen("queues:{$queue}");
                $this->line("     {$queue}: {$size} jobs");
            }

        } catch (\Exception $e) {
            $this->error("     Error: Failed to get queue sizes - {$e->getMessage()}");
        }
    }

    /**
     * Show failed jobs
     */
    private function showFailedJobs()
    {
        try {
            $redis = Redis::connection();
            $failedCount = $redis->llen('queues:failed');

            if ($failedCount > 0) {
                $this->line("   Failed Jobs: ❌ {$failedCount} failed jobs");
                $this->line("   Recommendation: Run 'php artisan queue:retry all'");
            } else {
                $this->line("   Failed Jobs: ✅ No failed jobs");
            }

        } catch (\Exception $e) {
            $this->line("   Failed Jobs: ⚠️  Unable to check");
        }
    }

    /**
     * Clear queue
     */
    private function clearQueue()
    {
        $this->info('🧹 Clearing Queue');

        $queue = $this->option('queue');
        $force = $this->option('force');

        if (!$force && !$this->confirm("Are you sure you want to clear the '{$queue}' queue?")) {
            $this->info('Queue clear cancelled.');
            return;
        }

        try {
            $redis = Redis::connection();

            if ($queue === 'all') {
                // Clear all queues
                $queues = ['default', 'notifications', 'high', 'low'];
                foreach ($queues as $q) {
                    $redis->del("queues:{$q}");
                }
                $this->line("   Status: ✅ All queues cleared");
            } else {
                // Clear specific queue
                $redis->del("queues:{$queue}");
                $this->line("   Status: ✅ Queue '{$queue}' cleared");
            }

        } catch (\Exception $e) {
            $this->error("   Error: ❌ Failed to clear queue - {$e->getMessage()}");
        }

        $this->line('');
    }

    /**
     * Monitor queue
     */
    private function monitorQueue()
    {
        $this->info('📊 Queue Monitoring');

        try {
            $redis = Redis::connection();

            $this->line("   Queue Metrics:");

            // Get queue sizes
            $queues = ['default', 'notifications', 'high', 'low'];
            foreach ($queues as $queue) {
                $size = $redis->llen("queues:{$queue}");
                $this->line("     {$queue}: {$size} jobs");
            }

            // Get failed jobs
            $failedCount = $redis->llen('queues:failed');
            $this->line("     failed: {$failedCount} jobs");

            // Get queue info
            $info = $redis->info();
            $this->line("   Redis Info:");
            $this->line("     Connected Clients: " . ($info['connected_clients'] ?? 0));
            $this->line("     Total Commands: " . ($info['total_commands_processed'] ?? 0));
            $this->line("     Commands/sec: " . ($info['instantaneous_ops_per_sec'] ?? 0));

        } catch (\Exception $e) {
            $this->error("   Error: ❌ Failed to monitor queue - {$e->getMessage()}");
        }

        $this->line('');
    }

    /**
     * Show queue statistics
     */
    private function showQueueStats()
    {
        $this->info('📈 Queue Statistics');

        try {
            $redis = Redis::connection();
            $info = $redis->info();

            $this->line("   Redis Statistics:");
            $this->line("     Version: " . ($info['redis_version'] ?? 'Unknown'));
            $this->line("     Uptime: " . $this->formatUptime($info['uptime_in_seconds'] ?? 0));
            $this->line("     Memory Used: " . $this->formatBytes($info['used_memory'] ?? 0));
            $this->line("     Memory Peak: " . $this->formatBytes($info['used_memory_peak'] ?? 0));
            $this->line("     Connected Clients: " . ($info['connected_clients'] ?? 0));
            $this->line("     Total Commands: " . ($info['total_commands_processed'] ?? 0));
            $this->line("     Commands/sec: " . ($info['instantaneous_ops_per_sec'] ?? 0));

            // Queue specific stats
            $this->line("   Queue Statistics:");
            $queues = ['default', 'notifications', 'high', 'low'];
            foreach ($queues as $queue) {
                $size = $redis->llen("queues:{$queue}");
                $this->line("     {$queue}: {$size} jobs");
            }

            $failedCount = $redis->llen('queues:failed');
            $this->line("     failed: {$failedCount} jobs");

        } catch (\Exception $e) {
            $this->error("   Error: ❌ Failed to get queue stats - {$e->getMessage()}");
        }

        $this->line('');
    }

    /**
     * Test queue
     */
    private function testQueue()
    {
        $this->info('🧪 Testing Queue');

        try {
            // Test notification job
            $this->line("   Testing notification job...");

            $testUserId = 1; // Use a test user ID
            $title = 'Test Notification';
            $body = 'This is a test notification from the queue system';
            $data = ['test' => true];

            SendNotificationJob::dispatch($testUserId, $title, $body, $data, 'test');

            $this->line("   Status: ✅ Test job dispatched");
            $this->line("   Check queue status to see the job");

        } catch (\Exception $e) {
            $this->error("   Error: ❌ Failed to test queue - {$e->getMessage()}");
        }

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
