<?php

namespace App\Notifications;

use App\Modules\Identity\Models\TeacherParentMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeacherParentMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly TeacherParentMessage $message,
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
        $this->message->loadMissing(['teacher', 'student']);

        $mail = (new MailMessage)
            ->subject('رسالة من المدرس '.$this->message->teacher?->name)
            ->greeting('مرحبًا '.$notifiable->name)
            ->line($this->message->body);

        if ($this->message->student) {
            $mail->line('بخصوص: '.$this->message->student->name);
        }

        return $mail->action('عرض الرسائل', url('/parent/messages'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->message->loadMissing(['teacher', 'student']);

        $teacherName = $this->message->teacher?->name ?? '';

        return [
            'type' => 'teacher_parent_message',
            'message_id' => $this->message->id,
            'teacher_id' => $this->message->teacher_id,
            'student_id' => $this->message->student_id,
            'message' => 'رسالة من المدرس '.$teacherName.': '.mb_substr($this->message->body, 0, 80),
            'url' => '/parent/messages',
        ];
    }
}
