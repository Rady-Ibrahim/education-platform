<?php

namespace App\Livewire\Teacher;

use App\Enums\ExamAttemptStatus;
use App\Enums\UserRole;
use App\Modules\Content\Services\ContentAccessService;
use App\Modules\Exams\Models\Exam;
use App\Modules\Exams\Models\ExamAttempt;
use App\Modules\Exams\Services\ExamAttemptService;
use Livewire\Component;

class ExamResults extends Component
{
    public int $examId;

    public function mount(int $examId, ContentAccessService $access): void
    {
        $exam = Exam::query()->with('subject')->findOrFail($examId);
        $user = auth()->user();

        $allowed = $user->hasRole(UserRole::Admin)
            || $exam->created_by === $user->id
            || $access->teacherOwnsSubject($user, $exam->subject);

        if (! $allowed) {
            abort(403);
        }

        $this->examId = $examId;
    }

    public function render(ExamAttemptService $service)
    {
        $exam = Exam::query()
            ->with(['subject.grade.stage', 'questions:id'])
            ->findOrFail($this->examId);

        $attempts = $exam->attempts()
            ->with('student:id,name,student_code')
            ->orderByDesc('id')
            ->get();

        $rows = $attempts->map(function (ExamAttempt $attempt) use ($service) {
            return (object) [
                'attempt' => $attempt,
                'summary' => $service->resultSummary($attempt),
            ];
        });

        $gradedRows = $rows->filter(fn (object $row) => ! in_array($row->attempt->status, [ExamAttemptStatus::InProgress], true));
        $totalAttempts = $attempts->count();
        $completedAttempts = $gradedRows->count();
        $passedCount = $gradedRows->filter(fn (object $row) => $row->summary['passed'] === true)->count();
        $scores = $gradedRows->pluck('summary.percent');

        $stats = [
            'students' => $totalAttempts,
            'completed' => $completedAttempts,
            'completion_rate' => $totalAttempts > 0 ? round($completedAttempts / $totalAttempts * 100, 1) : 0.0,
            'average' => $scores->isNotEmpty() ? round($scores->avg(), 1) : null,
            'highest' => $scores->isNotEmpty() ? $scores->max() : null,
            'lowest' => $scores->isNotEmpty() ? $scores->min() : null,
            'pass_rate' => $completedAttempts > 0 ? round($passedCount / $completedAttempts * 100, 1) : null,
            'pass_score' => $exam->pass_score !== null ? (float) $exam->pass_score : null,
        ];

        return view('livewire.teacher.exam-results', [
            'exam' => $exam,
            'rows' => $rows,
            'stats' => $stats,
        ]);
    }
}
