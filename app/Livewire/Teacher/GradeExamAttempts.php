<?php

namespace App\Livewire\Teacher;

use App\Enums\QuestionType;
use App\Enums\UserRole;
use App\Modules\Exams\Models\ExamAnswer;
use App\Modules\Exams\Models\ExamAttempt;
use App\Modules\Exams\Models\Question;
use App\Modules\Exams\Services\ExamAttemptService;
use Livewire\Component;

class GradeExamAttempts extends Component
{
    /** @var array<int, string> */
    public array $pointsInput = [];

    public function grade(int $attemptId, int $questionId, ExamAttemptService $attempts): void
    {
        $points = (float) ($this->pointsInput[$questionId.'-'.$attemptId] ?? 0);

        $this->validate([
            'pointsInput.'.$questionId.'-'.$attemptId => ['required', 'numeric', 'min:0'],
        ], [], [
            'pointsInput.'.$questionId.'-'.$attemptId => 'الدرجة',
        ]);

        $attempt = ExamAttempt::query()->with('exam.subject')->findOrFail($attemptId);
        $question = Question::query()->findOrFail($questionId);

        $attempts->gradeEssay(auth()->user(), $attempt, $question, $points);
        session()->flash('grade_status', 'تم حفظ درجة السؤال المقالي.');
    }

    public function render()
    {
        $teacherId = auth()->id();

        $answers = ExamAnswer::query()
            ->with(['question', 'attempt.student', 'attempt.exam.questions'])
            ->whereHas('question', fn ($q) => $q->where('type', QuestionType::Essay->value))
            ->whereHas('attempt.exam.subject.teachers', fn ($q) => $q->where('users.id', $teacherId))
            ->whereHas('attempt.student', fn ($q) => $q->role(UserRole::Student->value))
            ->latest('id')
            ->limit(50)
            ->get();

        foreach ($answers as $answer) {
            $key = $answer->question_id.'-'.$answer->attempt_id;
            if (! array_key_exists($key, $this->pointsInput)) {
                $this->pointsInput[$key] = (string) ($answer->points_awarded ?? '0');
            }
        }

        return view('livewire.teacher.grade-exam-attempts', [
            'answers' => $answers,
        ]);
    }
}
