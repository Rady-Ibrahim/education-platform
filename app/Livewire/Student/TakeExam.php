<?php

namespace App\Livewire\Student;

use App\Enums\ExamDeliveryMode;
use App\Modules\Exams\Models\Exam;
use App\Modules\Exams\Models\ExamAnswer;
use App\Modules\Exams\Models\ExamAttempt;
use App\Modules\Exams\Models\Question;
use App\Modules\Exams\Services\ExamAttemptService;
use Livewire\Component;

class TakeExam extends Component
{
    public ?int $examId = null;

    public ?int $attemptId = null;

    /** @var list<array<string, mixed>> */
    public array $questions = [];

    public ?float $resultScore = null;

    public ?float $resultMax = null;

    public function startExam(int $examId, ExamAttemptService $service): void
    {
        $exam = Exam::query()->findOrFail($examId);
        $attempt = $service->start(auth()->user(), $exam);

        $this->examId = $exam->id;
        $this->attemptId = $attempt->id;
        $this->questions = $service->questionsForStudent($attempt);
        $this->resultScore = null;
        $this->resultMax = null;
    }

    public function autosave(int $questionId, ?int $optionId, ?string $text, ExamAttemptService $service): void
    {
        if (! $this->attemptId) {
            return;
        }

        $attempt = ExamAttempt::query()->findOrFail($this->attemptId);
        $question = Question::query()->findOrFail($questionId);

        $service->autosaveAnswer(auth()->user(), $attempt, $question, [
            'selected_option_id' => $optionId,
            'answer_text' => $text,
        ]);
    }

    public function saveOption(int $questionId, int $optionId, ExamAttemptService $service): void
    {
        $this->autosave($questionId, $optionId, null, $service);
    }

    public function saveText(int $questionId, string $text, ExamAttemptService $service): void
    {
        $this->autosave($questionId, null, $text, $service);
    }

    public function submit(ExamAttemptService $service): void
    {
        $attempt = ExamAttempt::query()->findOrFail($this->attemptId);
        $attempt = $service->submit(auth()->user(), $attempt);

        $this->resultScore = (float) $attempt->score;
        $this->resultMax = (float) $attempt->max_score;
        $this->attemptId = null;
        $this->questions = [];
        session()->flash('exam_take_status', 'تم تسليم الامتحان.');
    }

    public function render()
    {
        $teacherIds = auth()->user()->teachers()->pluck('users.id');

        $availableExams = Exam::query()
            ->with('subject.grade')
            ->where('is_published', true)
            ->where('delivery_mode', ExamDeliveryMode::Online)
            ->whereHas('subject.teachers', fn ($q) => $q->whereIn('users.id', $teacherIds))
            ->latest()
            ->get()
            ->filter(fn (Exam $exam) => $exam->isAvailableNow());

        $savedAnswers = [];
        if ($this->attemptId) {
            $savedAnswers = ExamAnswer::query()
                ->where('attempt_id', $this->attemptId)
                ->get()
                ->keyBy('question_id');
        }

        return view('livewire.student.take-exam', [
            'availableExams' => $availableExams,
            'savedAnswers' => $savedAnswers,
        ]);
    }
}
