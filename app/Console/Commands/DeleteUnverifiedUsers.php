<?php

namespace App\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DeleteUnverifiedUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:delete-unverified';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete users who have not verified their email within 30 minutes of registration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $thirtyMinutesAgo = Carbon::now()->subMinutes(30);

        // Find unverified users created more than 30 minutes ago
        $users = User::whereNull('email_verified_at')
            ->where('created_at', '<=', $thirtyMinutesAgo)
            ->get();

        $count = $users->count();

        if ($count === 0) {
            $this->info('No unverified users to delete.');
            return 0;
        }

        // Delete the users
        foreach ($users as $user) {
            $this->info("Deleting unverified user: {$user->email}");
            $user->delete();
        }

        $this->info("Successfully deleted {$count} unverified user(s).");
        return 0;
    }
}
