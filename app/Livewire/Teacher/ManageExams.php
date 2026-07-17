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

    public int $durationMinutes = 30;

    public int $maxAttempts = 1;

    public string $startsAt = '';

    public string $endsAt = '';

    public string $passScore = '';

    /** @var list<int> */
    public array $selectedQuestionIds = [];

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

    public function saveQuestion(QuestionBankService $service): void
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

        $options = [];
        foreach ($this->options as $index => $option) {
            $options[] = [
                'label' => $option['label'],
                'is_correct' => $index === $this->correctOptionIndex,
            ];
        }

        $service->create(
            auth()->user(),
            Subject::query()->findOrFail($this->subjectId),
            [
                'type' => $this->questionType,
                'stem' => $this->questionStem,
                'points' => $this->points,
                'correct_answer' => $this->correctAnswer ?: null,
                'options' => $options,
            ]
        );

        $this->reset(['questionStem', 'correctAnswer']);
        $this->points = 1;
        $this->correctOptionIndex = 0;
        $this->options = [
            ['label' => ''],
            ['label' => ''],
        ];
        session()->flash('exam_status', 'تم حفظ السؤال في البنك.');
    }

    public function createExam(ExamService $service): void
    {
        $validated = $this->validate([
            'subjectId' => ['required', 'exists:subjects,id'],
            'examTitle' => ['required', 'string', 'max:255'],
            'durationMinutes' => ['required', 'integer', 'min:1'],
            'maxAttempts' => ['required', 'integer', 'min:1'],
            'startsAt' => ['nullable', 'date'],
            'endsAt' => ['nullable', 'date', 'after_or_equal:startsAt'],
            'passScore' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'selectedQuestionIds' => ['required', 'array', 'min:1'],
        ]);

        $exam = $service->create(auth()->user(), Subject::query()->findOrFail($validated['subjectId']), [
            'title' => $validated['examTitle'],
            'duration_minutes' => $validated['durationMinutes'],
            'max_attempts' => $validated['maxAttempts'],
            'starts_at' => $validated['startsAt'] ?: null,
            'ends_at' => $validated['endsAt'] ?: null,
            'pass_score' => $validated['passScore'] !== '' ? (float) $validated['passScore'] : null,
            'question_ids' => $validated['selectedQuestionIds'],
            'is_published' => true,
        ]);

        $this->reset(['examTitle', 'selectedQuestionIds', 'startsAt', 'endsAt', 'passScore']);
        $this->durationMinutes = 30;
        $this->maxAttempts = 1;
        session()->flash('exam_status', 'تم إنشاء ونشر الامتحان: '.$exam->title);
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
            ->latest()
            ->get();

        $exams = Exam::query()
            ->withCount('questions')
            ->where('subject_id', $this->subjectId)
            ->latest()
            ->get();

        return view('livewire.teacher.manage-exams', [
            'subjects' => $subjects,
            'questions' => $questions,
            'exams' => $exams,
            'types' => QuestionType::cases(),
        ]);
    }
}
