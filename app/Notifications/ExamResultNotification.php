<?php

namespace App\Notifications;

use App\Modules\Exams\Models\ExamAttempt;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExamResultNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ExamAttempt $attempt,
        public readonly string $audience = 'student',
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    private function url(): string
    {
        return $this->audience === 'parent' ? '/parent' : '/student/exams';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->attempt->loadMissing(['exam.subject', 'student']);

        $percent = $this->attempt->max_score > 0
            ? round((float) $this->attempt->score / (float) $this->attempt->max_score * 100, 1)
            : 0;

        $who = $this->audience === 'parent'
            ? 'نتيجة '.$this->attempt->student?->name
            : 'نتيجتك';

        return (new MailMessage)
            ->subject('نتيجة امتحان: '.$this->attempt->exam?->title)
            ->greeting('مرحبًا '.$notifiable->name)
            ->line($who.' في امتحان «'.$this->attempt->exam?->title.'»:')
            ->line($this->attempt->score.' / '.$this->attempt->max_score.' ('.$percent.'%)')
            ->action('عرض النتائج', url($this->url()));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->attempt->loadMissing('exam');

        return [
            'type' => 'exam_result',
            'exam_id' => $this->attempt->exam_id,
            'attempt_id' => $this->attempt->id,
            'title' => $this->attempt->exam?->title,
            'message' => 'نتيجة امتحان متاحة: '.$this->attempt->exam?->title,
            'score' => (float) $this->attempt->score,
            'max_score' => (float) $this->attempt->max_score,
            'url' => $this->url(),
        ];
    }
}
