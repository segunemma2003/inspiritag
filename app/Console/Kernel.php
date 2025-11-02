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
        
        $schedule->command('users:delete-unverified')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        
        $schedule->command('cache:warm-up')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        
        $schedule->command('performance:monitor')
            ->everyTenMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        
        $schedule->command('db:health-check')
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        
        $schedule->command('system:health-check')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();

        
        $schedule->command('cache:manage clear --pattern="*" --force')
            ->everySixHours()
            ->withoutOverlapping()
            ->runInBackground();

        
        $schedule->command('queue:manage monitor')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        
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
