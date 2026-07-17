<?php

namespace App\Notifications;

use App\Modules\Payments\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentRejectedNotification extends Notification implements ShouldQueue
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
        return (new MailMessage)
            ->subject('تم رفض إثبات الدفع')
            ->greeting('مرحبًا '.$notifiable->name)
            ->line('تم رفض دفعتك بمبلغ '.number_format((float) $this->payment->amount, 2).' ج.م.')
            ->line('السبب: '.($this->payment->rejection_reason ?: 'غير محدد'))
            ->action('إعادة إرسال الإثبات', url('/student/subscriptions'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_rejected',
            'payment_id' => $this->payment->id,
            'amount' => (float) $this->payment->amount,
            'rejection_reason' => $this->payment->rejection_reason,
            'message' => 'تم رفض إثبات الدفع: '.($this->payment->rejection_reason ?: 'غير محدد'),
            'url' => '/student/subscriptions',
        ];
    }
}
