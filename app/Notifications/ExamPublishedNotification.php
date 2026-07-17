<?php

namespace App\Notifications;

use App\Modules\Exams\Models\Exam;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExamPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Exam $exam,
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
        $this->exam->loadMissing('subject');

        return (new MailMessage)
            ->subject('امتحان جديد متاح: '.$this->exam->title)
            ->greeting('مرحبًا '.$notifiable->name)
            ->line('تم نشر امتحان «'.$this->exam->title.'» في مادة '.$this->exam->subject?->name.'.')
            ->action('دخول الامتحانات', url('/student/exams'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'exam_published',
            'exam_id' => $this->exam->id,
            'title' => $this->exam->title,
            'message' => 'امتحان جديد متاح: '.$this->exam->title,
            'url' => '/student/exams',
        ];
    }
}
