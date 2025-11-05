<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SubscriptionService;

class CheckSubscriptionExpiration extends Command
{
    protected $signature = 'subscriptions:check-expiration';
    protected $description = 'Check and expire professional subscriptions';

    public function handle()
    {
        $this->info('Checking subscription expiration...');
        
        $expiredCount = SubscriptionService::checkAndExpireSubscriptions();
        
        if ($expiredCount > 0) {
            $this->info("Expired {$expiredCount} subscription(s)");
        } else {
            $this->info('No expired subscriptions found');
        }
        
        return Command::SUCCESS;
    }
}

