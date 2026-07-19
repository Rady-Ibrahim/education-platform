<?php

namespace App\Livewire\Parent;

use App\Enums\ExamAttemptStatus;
use App\Models\User;
use App\Modules\Exams\Models\ExamAttempt;
use App\Modules\Identity\Services\ParentLinkService;
use Livewire\Component;
use Livewire\WithPagination;

class ChildExamResults extends Component
{
    use WithPagination;

    public int $studentId;

    public string $studentName = '';

    public function mount(int $studentId, ParentLinkService $links): void
    {
        $student = User::query()->findOrFail($studentId);

        if (! $links->parentCanViewStudent(auth()->user(), $student)) {
            abort(403);
        }

        $this->studentId = $student->id;
        $this->studentName = $student->name;
    }

    public function render()
    {
        $attempts = ExamAttempt::query()
            ->with(['exam:id,title,delivery_mode,subject_id', 'exam.subject:id,name'])
            ->where('student_id', $this->studentId)
            ->whereIn('status', [ExamAttemptStatus::Submitted, ExamAttemptStatus::Graded])
            ->whereNotNull('score')
            ->whereNotNull('max_score')
            ->latest('submitted_at')
            ->paginate(12);

        return view('livewire.parent.child-exam-results', [
            'attempts' => $attempts,
        ]);
    }
}
