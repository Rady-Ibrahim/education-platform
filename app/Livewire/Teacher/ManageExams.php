<?php

namespace App\Livewire\Teacher;

use App\Enums\QuestionType;
use App\Modules\Academic\Models\Subject;
use App\Modules\Exams\Models\Exam;
use App\Modules\Exams\Models\Question;
use App\Modules\Exams\Services\ExamService;
use App\Modules\Exams\Services\QuestionBankService;
use Livewire\Component;

class ManageExams extends Component
{
    public ?int $subjectId = null;

    public string $questionType = 'mcq';

    public string $questionStem = '';

    public string $correctAnswer = '';

    public float $points = 1;

    /** @var list<array{label: string, is_correct: bool}> */
    public array $options = [
        ['label' => ''],
        ['label' => ''],
    ];

    public int $correctOptionIndex = 0;

    public string $examTitle = '';

    public string $examDescription = '';

    public int $durationMinutes = 30;

    public int $maxAttempts = 1;

    public string $startsAt = '';

    public string $endsAt = '';

    public string $passScore = '';

    public bool $publishNow = true;

    /** @var list<int> */
    public array $selectedQuestionIds = [];

    public ?int $editingExamId = null;

    public ?int $editingQuestionId = null;

    public function mount(): void
    {
        $this->subjectId = Subject::query()
            ->whereHas('teachers', fn ($q) => $q->where('users.id', auth()->id()))
            ->orderBy('ordering')
            ->value('id');
    }

    public function addOption(): void
    {
        $this->options[] = ['label' => ''];
    }

    // ===== الأسئلة =====

    public function saveQuestion(QuestionBankService $service): void
    {
        $this->validateQuestionForm();

        $service->create(
            auth()->user(),
            Subject::query()->findOrFail($this->subjectId),
            $this->questionPayload()
        );

        $this->resetQuestionForm();
        session()->flash('exam_status', 'تم حفظ السؤال في البنك.');
    }

    public function loadQuestion(int $questionId): void
    {
        $question = Question::query()->with('options')->findOrFail($questionId);
        $question->loadMissing('subject');

        if (! app(\App\Modules\Content\Services\ContentAccessService::class)->teacherOwnsSubject(auth()->user(), $question->subject)) {
            return;
        }

        $this->editingQuestionId = $question->id;
        $this->questionType = $question->type->value;
        $this->questionStem = $question->stem;
        $this->points = (float) $question->points;
        $this->correctAnswer = (string) ($question->correct_answer ?? '');

        if ($question->type === QuestionType::Mcq) {
            $this->options = $question->options->map(fn ($option) => ['label' => $option->label])->values()->all();
            $this->correctOptionIndex = max(0, $question->options->search(fn ($option) => $option->is_correct));
        } else {
            $this->options = [['label' => ''], ['label' => '']];
            $this->correctOptionIndex = 0;
        }
    }

    public function updateQuestion(QuestionBankService $service): void
    {
        if (! $this->editingQuestionId) {
            return;
        }

        $this->validateQuestionForm();

        $service->update(
            auth()->user(),
            Question::query()->findOrFail($this->editingQuestionId),
            $this->questionPayload()
        );

        $this->resetQuestionForm();
        session()->flash('exam_status', 'تم تحديث السؤال.');
    }

    public function deleteQuestion(int $questionId, QuestionBankService $service): void
    {
        $service->delete(auth()->user(), Question::query()->findOrFail($questionId));
        session()->flash('exam_status', 'تم حذف السؤال من البنك.');
    }

    public function cancelQuestionEdit(): void
    {
        $this->resetQuestionForm();
    }

    // ===== الامتحانات =====

    public function createExam(ExamService $service): void
    {
        $validated = $this->validateExamForm();

        $exam = $service->create(auth()->user(), Subject::query()->findOrFail($validated['subjectId']), [
            'title' => $validated['examTitle'],
            'description' => $this->examDescription ?: null,
            'duration_minutes' => $validated['durationMinutes'],
            'max_attempts' => $validated['maxAttempts'],
            'starts_at' => $validated['startsAt'] ?: null,
            'ends_at' => $validated['endsAt'] ?: null,
            'pass_score' => $validated['passScore'] !== '' ? (float) $validated['passScore'] : null,
            'question_ids' => $validated['selectedQuestionIds'] ?? [],
            'is_published' => $this->publishNow,
        ]);

        $this->resetExamForm();
        session()->flash('exam_status', $exam->is_published
            ? 'تم إنشاء ونشر الامتحان: '.$exam->title
            : 'تم حفظ الامتحان كمسودة: '.$exam->title);
    }

    public function loadExam(int $examId): void
    {
        $exam = Exam::query()->findOrFail($examId);

        if ($exam->created_by !== auth()->id()) {
            return;
        }

        $this->editingExamId = $exam->id;
        $this->examTitle = $exam->title;
        $this->examDescription = (string) ($exam->description ?? '');
        $this->durationMinutes = (int) ($exam->duration_minutes ?? 30);
        $this->maxAttempts = $exam->max_attempts;
        $this->startsAt = $exam->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->endsAt = $exam->ends_at?->format('Y-m-d\TH:i') ?? '';
        $this->passScore = $exam->pass_score !== null ? (string) $exam->pass_score : '';
        $this->selectedQuestionIds = $exam->questions()->pluck('questions.id')->all();
        $this->publishNow = $exam->is_published;
    }

    public function updateExam(ExamService $service): void
    {
        if (! $this->editingExamId) {
            return;
        }

        $validated = $this->validateExamUpdate();

        $exam = $service->update(auth()->user(), Exam::query()->findOrFail($this->editingExamId), [
            'title' => $validated['examTitle'],
            'description' => $this->examDescription ?: null,
            'duration_minutes' => $validated['durationMinutes'],
            'max_attempts' => $validated['maxAttempts'],
            'starts_at' => $validated['startsAt'] ?: null,
            'ends_at' => $validated['endsAt'] ?: null,
            'pass_score' => $validated['passScore'] !== '' ? (float) $validated['passScore'] : null,
            'question_ids' => $validated['selectedQuestionIds'] ?? [],
        ]);

        if ($this->publishNow !== $exam->is_published) {
            $exam = $service->publish(auth()->user(), $exam, $this->publishNow);
        }

        $this->resetExamForm();
        session()->flash('exam_status', 'تم تحديث الامتحان: '.$exam->title);
    }

    public function deleteExam(int $examId, ExamService $service): void
    {
        $service->delete(auth()->user(), Exam::query()->findOrFail($examId));
        session()->flash('exam_status', 'تم حذف الامتحان.');
    }

    public function togglePublish(int $examId, ExamService $service): void
    {
        $exam = Exam::query()->findOrFail($examId);
        $updated = $service->publish(auth()->user(), $exam, ! $exam->is_published);

        session()->flash('exam_status', $updated->is_published ? 'تم نشر الامتحان.' : 'تم إلغاء نشر الامتحان.');
    }

    public function cancelExamEdit(): void
    {
        $this->resetExamForm();
    }

    public function render()
    {
        $subjects = Subject::query()
            ->with('grade.stage')
            ->whereHas('teachers', fn ($q) => $q->where('users.id', auth()->id()))
            ->orderBy('ordering')
            ->get();

        $questions = Question::query()
            ->where('subject_id', $this->subjectId)
            ->where('is_active', true)
            ->withCount('exams')
            ->latest()
            ->get();

        $exams = Exam::query()
            ->withCount(['questions', 'attempts'])
            ->where('subject_id', $this->subjectId)
            ->where('created_by', auth()->id())
            ->latest()
            ->get();

        return view('livewire.teacher.manage-exams', [
            'subjects' => $subjects,
            'questions' => $questions,
            'exams' => $exams,
            'types' => QuestionType::cases(),
        ]);
    }

    // ===== قواعد التحقق والمساعدات =====

    private function validateQuestionForm(): void
    {
        $rules = [
            'subjectId' => ['required', 'exists:subjects,id'],
            'questionType' => ['required', 'in:mcq,true_false,essay,fill_blank'],
            'questionStem' => ['required', 'string'],
            'points' => ['required', 'numeric', 'min:0.5'],
        ];

        if (in_array($this->questionType, ['true_false', 'fill_blank'], true)) {
            $rules['correctAnswer'] = ['required', 'string'];
        }

        if ($this->questionType === 'mcq') {
            $rules['options'] = ['required', 'array', 'min:2'];
            $rules['options.*.label'] = ['required', 'string'];
            $rules['correctOptionIndex'] = ['required', 'integer', 'min:0'];
        }

        $this->validate($rules);
    }

    private function questionPayload(): array
    {
        $options = [];
        foreach ($this->options as $index => $option) {
            $options[] = [
                'label' => $option['label'],
                'is_correct' => $index === $this->correctOptionIndex,
            ];
        }

        return [
            'type' => $this->questionType,
            'stem' => $this->questionStem,
            'points' => $this->points,
            'correct_answer' => $this->correctAnswer ?: null,
            'options' => $options,
        ];
    }

    private function validateExamForm(): array
    {
        $rules = [
            'subjectId' => ['required', 'exists:subjects,id'],
            'examTitle' => ['required', 'string', 'max:255'],
            'durationMinutes' => ['required', 'integer', 'min:1'],
            'maxAttempts' => ['required', 'integer', 'min:1'],
            'startsAt' => ['nullable', 'date'],
            'endsAt' => ['nullable', 'date', 'after_or_equal:startsAt'],
            'passScore' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];

        if ($this->publishNow) {
            $rules['selectedQuestionIds'] = ['required', 'array', 'min:1'];
        } else {
            $rules['selectedQuestionIds'] = ['nullable', 'array'];
        }

        return $this->validate($rules);
    }

    private function validateExamUpdate(): array
    {
        return $this->validate([
            'examTitle' => ['required', 'string', 'max:255'],
            'durationMinutes' => ['required', 'integer', 'min:1'],
            'maxAttempts' => ['required', 'integer', 'min:1'],
            'startsAt' => ['nullable', 'date'],
            'endsAt' => ['nullable', 'date', 'after_or_equal:startsAt'],
            'passScore' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'selectedQuestionIds' => ['nullable', 'array'],
        ]);
    }

    private function resetQuestionForm(): void
    {
        $this->editingQuestionId = null;
        $this->questionStem = '';
        $this->correctAnswer = '';
        $this->points = 1;
        $this->correctOptionIndex = 0;
        $this->options = [
            ['label' => ''],
            ['label' => ''],
        ];
    }

    private function resetExamForm(): void
    {
        $this->editingExamId = null;
        $this->examTitle = '';
        $this->examDescription = '';
        $this->selectedQuestionIds = [];
        $this->startsAt = '';
        $this->endsAt = '';
        $this->passScore = '';
        $this->durationMinutes = 30;
        $this->maxAttempts = 1;
        $this->publishNow = true;
    }
}
