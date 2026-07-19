<?php

namespace App\Livewire\Teacher;

use App\Enums\ExamDeliveryMode;
use App\Models\User;
use App\Modules\Academic\Models\Subject;
use App\Modules\Exams\Models\Exam;
use App\Modules\Exams\Models\ExamAttempt;
use App\Modules\Exams\Services\ExamAttemptService;
use App\Modules\Exams\Services\ExamService;
use Livewire\Component;
use Livewire\WithFileUploads;

class ManualExamGrades extends Component
{
    use WithFileUploads;

    public ?int $subjectId = null;

    public string $paperTitle = '';

    public string $manualMaxScore = '100';

    public string $passScore = '50';

    public $paperFile = null;

    public ?int $gradeExamId = null;

    public ?int $gradeStudentId = null;

    public string $gradeScore = '';

    public function mount(): void
    {
        $this->subjectId = Subject::query()
            ->whereHas('teachers', fn ($q) => $q->where('users.id', auth()->id()))
            ->orderBy('ordering')
            ->value('id');
    }

    public function createPaperExam(ExamService $exams): void
    {
        $validated = $this->validate([
            'subjectId' => ['required', 'exists:subjects,id'],
            'paperTitle' => ['required', 'string', 'max:255'],
            'manualMaxScore' => ['required', 'numeric', 'min:1'],
            'passScore' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'paperFile' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $exam = $exams->create(auth()->user(), Subject::query()->findOrFail($validated['subjectId']), [
            'title' => $validated['paperTitle'],
            'delivery_mode' => ExamDeliveryMode::Paper->value,
            'manual_max_score' => (float) $validated['manualMaxScore'],
            'pass_score' => $validated['passScore'] !== '' ? (float) $validated['passScore'] : null,
            'paper' => $this->paperFile,
            'is_published' => true,
            'max_attempts' => 1,
        ]);

        $this->reset(['paperTitle', 'paperFile']);
        $this->manualMaxScore = '100';
        $this->passScore = '50';
        $this->gradeExamId = $exam->id;

        session()->flash('manual_status', 'تم إنشاء الامتحان الورقي: '.$exam->title);
    }

    public function saveManualGrade(ExamAttemptService $attempts): void
    {
        $validated = $this->validate([
            'gradeExamId' => ['required', 'exists:exams,id'],
            'gradeStudentId' => ['required', 'exists:users,id'],
            'gradeScore' => ['required', 'numeric', 'min:0'],
        ]);

        $exam = Exam::query()->findOrFail($validated['gradeExamId']);
        $student = User::query()->findOrFail($validated['gradeStudentId']);

        $attempt = $attempts->recordManualScore(
            auth()->user(),
            $exam,
            $student,
            (float) $validated['gradeScore'],
        );

        $this->gradeScore = '';
        session()->flash(
            'manual_status',
            'تم تسجيل درجة '.$student->name.': '.$attempt->score.'/'.$attempt->max_score
        );
    }

    public function render()
    {
        $teacher = auth()->user();

        $subjects = Subject::query()
            ->with('grade.stage')
            ->whereHas('teachers', fn ($q) => $q->where('users.id', $teacher->id))
            ->orderBy('ordering')
            ->get();

        $paperExams = Exam::query()
            ->where('subject_id', $this->subjectId)
            ->where('delivery_mode', ExamDeliveryMode::Paper)
            ->latest()
            ->get();

        $students = $teacher->students()->orderBy('name')->get(['users.id', 'users.name', 'users.student_code']);

        $recentGrades = ExamAttempt::query()
            ->with(['exam:id,title', 'student:id,name'])
            ->whereHas('exam', fn ($q) => $q
                ->where('created_by', $teacher->id)
                ->where('delivery_mode', ExamDeliveryMode::Paper))
            ->latest('submitted_at')
            ->limit(15)
            ->get();

        return view('livewire.teacher.manual-exam-grades', [
            'subjects' => $subjects,
            'paperExams' => $paperExams,
            'students' => $students,
            'recentGrades' => $recentGrades,
        ]);
    }
}
