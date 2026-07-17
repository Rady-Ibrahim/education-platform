<?php

namespace App\Notifications;

use App\Modules\Payments\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Payment $payment,
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
        $this->payment->loadMissing(['subscription.plan', 'subscription.subject']);

        return (new MailMessage)
            ->subject('تم تأكيد الدفع وتفعيل الاشتراك')
            ->greeting('مرحبًا '.$notifiable->name)
            ->line('تم تأكيد دفعتك بمبلغ '.number_format((float) $this->payment->amount, 2).' ج.م.')
            ->line('تم تفعيل اشتراكك في: '.$this->payment->subscription?->subject?->name)
            ->action('عرض الاشتراكات', url('/student/subscriptions'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_confirmed',
            'payment_id' => $this->payment->id,
            'amount' => (float) $this->payment->amount,
            'message' => 'تم تأكيد الدفع وتفعيل الاشتراك.',
            'url' => '/student/subscriptions',
        ];
    }
}
