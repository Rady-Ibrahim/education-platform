<?php

namespace App\Livewire\Parent;

use App\Enums\ExamAttemptStatus;
use App\Enums\ParentLinkStatus;
use App\Modules\Exams\Models\ExamAttempt;
use Livewire\Component;

class ParentExamOverview extends Component
{
    public function render()
    {
        $children = auth()->user()
            ->children()
            ->wherePivot('status', ParentLinkStatus::Active->value)
            ->orderBy('name')
            ->get(['users.id', 'users.name', 'users.student_code']);

        $childIds = $children->pluck('id');

        $attempts = ExamAttempt::query()
            ->with(['exam:id,title,delivery_mode,subject_id', 'exam.subject:id,name', 'student:id,name'])
            ->whereIn('student_id', $childIds)
            ->whereIn('status', [ExamAttemptStatus::Submitted, ExamAttemptStatus::Graded])
            ->whereNotNull('score')
            ->whereNotNull('max_score')
            ->latest('submitted_at')
            ->limit(50)
            ->get()
            ->groupBy('student_id');

        return view('livewire.parent.parent-exam-overview', [
            'children' => $children,
            'attemptsByChild' => $attempts,
        ]);
    }
}
