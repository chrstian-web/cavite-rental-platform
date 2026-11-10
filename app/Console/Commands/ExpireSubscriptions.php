<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Move owners whose paid plan has ended back to Free and hide listings over the Free limit';

    public function handle(SubscriptionService $subscriptions): int
    {
        $count = $subscriptions->expireDue();
        $this->info("Expired {$count} subscription(s).");

        return self::SUCCESS;
    }
}
