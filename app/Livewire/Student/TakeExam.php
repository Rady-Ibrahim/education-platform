<?php

namespace App\Livewire\Student;

use App\Enums\ExamAttemptStatus;
use App\Enums\ExamDeliveryMode;
use App\Modules\Exams\Models\Exam;
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

    /** @var array<int, array{option_id: int|null, text: string|null}> */
    public array $answers = [];

    public int $currentIndex = 0;

    public bool $showReview = false;

    public bool $hasTimer = false;

    public int $remainingSeconds = 0;

    public ?int $previewExamId = null;

    public string $draftText = '';

    public ?float $resultScore = null;

    public ?float $resultMax = null;

    public ?float $resultPercent = null;

    public ?bool $resultPassed = null;

    public int $resultTotal = 0;

    public int $resultAnswered = 0;

    public int $resultCorrect = 0;

    public int $resultWrong = 0;

    public int $resultUnanswered = 0;

    public function mount(ExamAttemptService $service): void
    {
        // استكمال المحاولة الجارية تلقائيًا بعد الـrefresh أو إغلاق الصفحة.
        $attempt = ExamAttempt::query()
            ->where('student_id', auth()->id())
            ->where('status', ExamAttemptStatus::InProgress)
            ->whereHas('exam', fn ($q) => $q
                ->where('is_published', true)
                ->where('delivery_mode', ExamDeliveryMode::Online))
            ->orderBy('id')
            ->first();

        if ($attempt) {
            $this->loadAttempt($service, $attempt);
        }
    }

    public function previewExam(int $examId): void
    {
        $this->previewExamId = $examId;
    }

    public function cancelPreview(): void
    {
        $this->previewExamId = null;
    }

    public function startExam(int $examId, ExamAttemptService $service): void
    {
        $exam = Exam::query()->findOrFail($examId);
        $attempt = $service->start(auth()->user(), $exam);

        $this->resetResult();
        $this->loadAttempt($service, $attempt);
        $this->previewExamId = null;
    }

    public function saveOption(int $questionId, int $optionId, ExamAttemptService $service): void
    {
        if (! $this->guardRunner()) {
            return;
        }

        $this->answers[$questionId] = ['option_id' => $optionId, 'text' => null];
        $this->persistAnswer($questionId, $optionId, null, $service);
    }

    /**
     * Autosave نصي مع debounce من textarea السؤال الحالي.
     */
    public function updatedDraftText(string $value): void
    {
        if (! $this->guardRunner()) {
            return;
        }

        $questionId = $this->currentQuestionId();

        if (! $questionId) {
            return;
        }

        $this->answers[$questionId] = ['option_id' => null, 'text' => $value];
        $this->persistAnswer($questionId, null, $value, app(ExamAttemptService::class));
    }

    public function next(): void
    {
        if ($this->currentIndex < count($this->questions) - 1) {
            $this->currentIndex++;
            $this->syncDraftText();
        }
    }

    public function previous(): void
    {
        if ($this->currentIndex > 0) {
            $this->currentIndex--;
            $this->syncDraftText();
        }
    }

    public function goTo(int $index): void
    {
        if ($index >= 0 && $index < count($this->questions)) {
            $this->currentIndex = $index;
            $this->syncDraftText();
        }
    }

    public function openReview(): void
    {
        $this->showReview = true;
    }

    public function closeReview(): void
    {
        $this->showReview = false;
    }

    public function submitExam(ExamAttemptService $service): void
    {
        if (! $this->attemptId) {
            return;
        }

        $attempt = ExamAttempt::query()->find($this->attemptId);

        if (! $attempt || ! $attempt->isInProgress()) {
            $this->finishToResult($attempt);

            return;
        }

        $graded = $service->submit(auth()->user(), $attempt);

        session()->flash('exam_take_status', 'تم تسليم الامتحان بنجاح.');
        $this->loadResult($graded);
    }

    /**
     * تسليم تلقائي عند وصول العدّاد إلى صفر.
     */
    public function autoSubmit(ExamAttemptService $service): void
    {
        if (! $this->attemptId) {
            return;
        }

        $attempt = ExamAttempt::query()->find($this->attemptId);

        if (! $attempt || ! $attempt->isInProgress()) {
            $this->finishToResult($attempt);

            return;
        }

        $graded = $service->submit(auth()->user(), $attempt);

        session()->flash('exam_take_status', 'انتهى وقت الامتحان وتم التسليم تلقائيًا.');
        $this->loadResult($graded);
    }

    public function backToList(): void
    {
        $this->resetRunner();
        $this->resetResult();
        $this->previewExamId = null;
    }

    public function render(ExamAttemptService $service)
    {
        $teacherIds = auth()->user()->teachers()->pluck('users.id');

        $availableExams = Exam::query()
            ->with('subject.grade')
            ->where('is_published', true)
            ->where('delivery_mode', ExamDeliveryMode::Online)
            ->whereHas('subject.teachers', fn ($q) => $q->whereIn('users.id', $teacherIds))
            ->withCount('questions')
            ->latest()
            ->get()
            ->filter(fn (Exam $exam) => $exam->isAvailableNow());

        $attemptsByExam = ExamAttempt::query()
            ->where('student_id', auth()->id())
            ->whereIn('exam_id', $availableExams->pluck('id'))
            ->get()
            ->groupBy('exam_id')
            ->map->count();

        if ($this->attemptId) {
            $attempt = ExamAttempt::query()->find($this->attemptId);

            if ($attempt && $attempt->isInProgress()) {
                // مصدر الحقيقة للوقت هو الخادم — يُصحح أي انحراف في كل طلب.
                $remaining = $service->remainingSeconds($attempt);

                if ($remaining !== null) {
                    $this->remainingSeconds = $remaining;
                    $this->hasTimer = true;
                } else {
                    $this->hasTimer = false;
                }
            } else {
                $this->finishToResult($attempt);
            }
        }

        $reviewItems = collect($this->questions)->map(fn (array $question, int $index) => [
            'index' => $index,
            'id' => $question['id'],
            'answered' => $this->isAnswered((int) $question['id']),
        ]);

        $unansweredCount = $reviewItems->where('answered', false)->count();

        return view('livewire.student.take-exam', [
            'availableExams' => $availableExams,
            'attemptsByExam' => $attemptsByExam,
            'currentQuestion' => $this->questions[$this->currentIndex] ?? null,
            'reviewItems' => $reviewItems,
            'unansweredCount' => $unansweredCount,
            'answeredCount' => $reviewItems->where('answered', true)->count(),
        ]);
    }

    private function loadAttempt(ExamAttemptService $service, ExamAttempt $attempt): void
    {
        $this->examId = $attempt->exam_id;
        $this->attemptId = $attempt->id;
        $this->questions = $service->questionsForStudent($attempt);
        $this->hasTimer = $attempt->exam->duration_minutes !== null;
        $this->remainingSeconds = $service->remainingSeconds($attempt) ?? 0;
        $this->currentIndex = 0;
        $this->showReview = false;
        $this->draftText = '';

        $this->answers = [];
        foreach ($attempt->answers as $answer) {
            $this->answers[$answer->question_id] = [
                'option_id' => $answer->selected_option_id,
                'text' => $answer->answer_text,
            ];
        }
    }

    private function loadResult(ExamAttempt $attempt): void
    {
        $summary = app(ExamAttemptService::class)->resultSummary($attempt);

        $this->resultScore = $summary['score'];
        $this->resultMax = $summary['max_score'];
        $this->resultPercent = $summary['percent'];
        $this->resultPassed = $summary['passed'];
        $this->resultTotal = $summary['total'];
        $this->resultAnswered = $summary['answered'];
        $this->resultCorrect = $summary['correct'];
        $this->resultWrong = $summary['wrong'];
        $this->resultUnanswered = $summary['unanswered'];

        $this->resetRunner();
    }

    private function finishToResult(?ExamAttempt $attempt): void
    {
        if ($attempt && ! $attempt->isInProgress()) {
            session()->flash('exam_take_status', 'تم تسليم هذا الامتحان مسبقًا.');
            $this->loadResult($attempt);

            return;
        }

        $this->resetRunner();
    }

    private function persistAnswer(int $questionId, ?int $optionId, ?string $text, ExamAttemptService $service): void
    {
        $attempt = ExamAttempt::query()->find($this->attemptId);
        $question = Question::query()->find($questionId);

        if (! $attempt || ! $question) {
            return;
        }

        try {
            $service->autosaveAnswer(auth()->user(), $attempt, $question, [
                'selected_option_id' => $optionId,
                'answer_text' => $text,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // انتهى الوقت أثناء الحفظ — سيُسلَّم تلقائيًا ويُعرض التنبيه.
            if ($attempt->fresh() && ! $attempt->fresh()->isInProgress()) {
                $this->autoSubmit($service);
            }
        }
    }

    private function isAnswered(int $questionId): bool
    {
        $answer = $this->answers[$questionId] ?? null;

        if (! $answer) {
            return false;
        }

        return filled($answer['option_id']) || filled(trim((string) ($answer['text'] ?? '')));
    }

    private function currentQuestionId(): ?int
    {
        return $this->questions[$this->currentIndex]['id'] ?? null;
    }

    private function syncDraftText(): void
    {
        $questionId = $this->currentQuestionId();

        if (! $questionId || ($this->answers[$questionId]['option_id'] ?? null)) {
            $this->draftText = '';

            return;
        }

        $this->draftText = (string) ($this->answers[$questionId]['text'] ?? '');
    }

    private function guardRunner(): bool
    {
        return $this->attemptId !== null && $this->questions !== [];
    }

    private function resetRunner(): void
    {
        $this->examId = null;
        $this->attemptId = null;
        $this->questions = [];
        $this->answers = [];
        $this->currentIndex = 0;
        $this->showReview = false;
        $this->hasTimer = false;
        $this->remainingSeconds = 0;
        $this->draftText = '';
    }

    private function resetResult(): void
    {
        $this->resultScore = null;
        $this->resultMax = null;
        $this->resultPercent = null;
        $this->resultPassed = null;
        $this->resultTotal = 0;
        $this->resultAnswered = 0;
        $this->resultCorrect = 0;
        $this->resultWrong = 0;
        $this->resultUnanswered = 0;
    }
}
