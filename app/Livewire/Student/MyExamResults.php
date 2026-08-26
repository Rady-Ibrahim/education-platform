<?php

namespace App\Livewire\Student;

use App\Enums\ExamAttemptStatus;
use App\Modules\Exams\Models\ExamAttempt;
use App\Modules\Exams\Services\ExamAttemptService;
use Livewire\Component;

class MyExamResults extends Component
{
    public function render(ExamAttemptService $service)
    {
        $attempts = ExamAttempt::query()
            ->with(['exam:id,title,delivery_mode,subject_id', 'exam.subject:id,name'])
            ->where('student_id', auth()->id())
            ->whereIn('status', [ExamAttemptStatus::Submitted, ExamAttemptStatus::Graded])
            ->whereNotNull('score')
            ->whereNotNull('max_score')
            ->latest('submitted_at')
            ->limit(30)
            ->get();

        return view('livewire.student.my-exam-results', [
            'rows' => $attempts->map(fn (ExamAttempt $attempt) => (object) [
                'attempt' => $attempt,
                'summary' => $service->resultSummary($attempt),
            ]),
        ]);
    }
}
