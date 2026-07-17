<?php

namespace App\Notifications;

use App\Modules\Payments\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentPendingReviewNotification extends Notification implements ShouldQueue
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
        $this->payment->loadMissing('student');

        return (new MailMessage)
            ->subject('دفعة جديدة بانتظار المراجعة')
            ->greeting('مرحبًا '.$notifiable->name)
            ->line('الطالب '.$this->payment->student?->name.' أرسل إثبات دفع بمبلغ '.number_format((float) $this->payment->amount, 2).' ج.م.')
            ->action('مراجعة المدفوعات', url('/teacher/payments'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_pending_review',
            'payment_id' => $this->payment->id,
            'student_id' => $this->payment->student_id,
            'amount' => (float) $this->payment->amount,
            'message' => 'دفعة جديدة بانتظار مراجعتك.',
            'url' => '/teacher/payments',
        ];
    }
}
