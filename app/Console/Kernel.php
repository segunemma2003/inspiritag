<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Cache warming - every 5 minutes
        $schedule->command('cache:warm-up')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Performance monitoring - every 10 minutes
        $schedule->command('performance:monitor')
            ->everyTenMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Database health check - every 30 minutes
        $schedule->command('db:health-check')
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // System health check - every hour
        $schedule->command('system:health-check')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();

        // Cache cleanup - every 6 hours
        $schedule->command('cache:manage clear --pattern="*" --force')
            ->everySixHours()
            ->withoutOverlapping()
            ->runInBackground();

        // Queue monitoring - every 15 minutes
        $schedule->command('queue:manage monitor')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Performance testing - daily at 2 AM
        $schedule->command('performance:test --users=1000 --posts=100 --duration=60 --concurrent=10')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
