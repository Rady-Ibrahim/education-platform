<?php

namespace App\Console\Commands;

use App\Modules\Notifications\Services\NotificationService;
use Illuminate\Console\Command;

class NotifyExpiringSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:notify-expiring {--days=3 : Days before expiry}';

    protected $description = 'Send reminders for subscriptions expiring soon';

    public function handle(NotificationService $notifications): int
    {
        $days = (int) $this->option('days');
        $sent = $notifications->notifyExpiringSubscriptions($days);

        $this->info("Sent {$sent} expiring subscription reminder(s).");

        return self::SUCCESS;
    }
}
