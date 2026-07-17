<?php

namespace App\Notifications;

use App\Modules\Payments\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Subscription $subscription,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->subscription->loadMissing(['subject', 'plan']);

        return (new MailMessage)
            ->subject('اشتراكك على وشك الانتهاء')
            ->greeting('مرحبًا '.$notifiable->name)
            ->line('اشتراكك في '.$this->subscription->subject?->name.' ينتهي في '.$this->subscription->ends_at?->format('Y-m-d').'.')
            ->action('تجديد الاشتراك', url('/student/subscriptions'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'subscription_expiring',
            'subscription_id' => $this->subscription->id,
            'ends_at' => $this->subscription->ends_at?->toIso8601String(),
            'message' => 'اشتراكك على وشك الانتهاء.',
            'url' => '/student/subscriptions',
        ];
    }
}
