<?php

namespace App\Modules\Exams\Services;

use App\Enums\ExamAttemptStatus;
use App\Enums\QuestionType;
use App\Models\User;
use App\Modules\Certificates\Services\CertificateService;
use App\Modules\Content\Services\ContentAccessService;
use App\Modules\Exams\Models\Exam;
use App\Modules\Exams\Models\ExamAnswer;
use App\Modules\Exams\Models\ExamAttempt;
use App\Modules\Exams\Models\Question;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamAttemptService
{
    public function __construct(
        private readonly ContentAccessService $access,
        private readonly CertificateService $certificates,
    ) {}

    public function start(User $student, Exam $exam): ExamAttempt
    {
        $exam->loadMissing('subject');

        if (! $this->access->studentCanAccessSubject($student, $exam->subject)) {
            throw ValidationException::withMessages([
                'exam' => 'غير مصرح بدخول هذا الامتحان.',
            ]);
        }

        if (! $exam->isAvailableNow()) {
            throw ValidationException::withMessages([
                'exam' => 'الامتحان غير متاح حاليًا.',
            ]);
        }

        $existingInProgress = ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->where('status', ExamAttemptStatus::InProgress)
            ->first();

        if ($existingInProgress) {
            if (! $existingInProgress->hasExpired()) {
                return $existingInProgress;
            }

            $this->submit($student, $existingInProgress);
        }

        $attemptsCount = ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->count();

        if ($attemptsCount >= $exam->max_attempts) {
            throw ValidationException::withMessages([
                'exam' => 'تم استنفاد عدد المحاولات المسموح.',
            ]);
        }

        return ExamAttempt::query()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'status' => ExamAttemptStatus::InProgress,
            'started_at' => now(),
        ]);
    }

    /**
     * Autosave إجابة واحدة فورًا.
     *
     * @param  array{selected_option_id?: int|null, answer_text?: string|null}  $payload
     */
    public function autosaveAnswer(User $student, ExamAttempt $attempt, Question $question, array $payload): ExamAnswer
    {
        $this->assertOwnedInProgress($student, $attempt);

        if ($attempt->hasExpired()) {
            $this->submit($student, $attempt);
            throw ValidationException::withMessages([
                'attempt' => 'انتهى وقت الامتحان وتم التسليم تلقائيًا.',
            ]);
        }

        if (! $attempt->exam->questions()->where('questions.id', $question->id)->exists()) {
            throw ValidationException::withMessages([
                'question' => 'السؤال غير تابع لهذا الامتحان.',
            ]);
        }

        $answer = ExamAnswer::query()->updateOrCreate(
            [
                'attempt_id' => $attempt->id,
                'question_id' => $question->id,
            ],
            [
                'selected_option_id' => $payload['selected_option_id'] ?? null,
                'answer_text' => $payload['answer_text'] ?? null,
                'saved_at' => now(),
            ]
        );

        return $answer->refresh();
    }

    public function submit(User $student, ExamAttempt $attempt): ExamAttempt
    {
        $this->assertOwnedAttempt($student, $attempt);

        if (! $attempt->isInProgress()) {
            return $attempt;
        }

        $graded = DB::transaction(function () use ($attempt) {
            $this->gradeAttempt($attempt);

            $attempt->update([
                'status' => ExamAttemptStatus::Graded,
                'submitted_at' => now(),
            ]);

            return $attempt->refresh();
        });

        $this->certificates->issueForPassedAttempt($graded);

        return $graded;
    }

    /**
     * أسئلة المحاولة بدون تسريب الإجابات الصحيحة.
     *
     * @return list<array<string, mixed>>
     */
    public function questionsForStudent(ExamAttempt $attempt): array
    {
        $questions = $attempt->exam->questions()->with('options')->get();

        if ($attempt->exam->shuffle_questions) {
            $questions = $questions->shuffle()->values();
        }

        return $questions->map(function (Question $question) use ($attempt) {
            $payload = $question->toStudentArray();
            if ($attempt->exam->shuffle_questions) {
                $payload['options'] = collect($payload['options'])->shuffle()->values()->all();
            }

            return $payload;
        })->all();
    }

    public function gradeAttempt(ExamAttempt $attempt): void
    {
        $attempt->loadMissing(['answers', 'exam.questions.options']);

        $score = 0;
        $maxScore = 0;

        foreach ($attempt->exam->questions as $question) {
            $points = (float) ($question->pivot->points ?? $question->points);
            $maxScore += $points;

            $answer = $attempt->answers->firstWhere('question_id', $question->id);
            if (! $answer) {
                continue;
            }

            if (! $question->type->isAutoGradable()) {
                continue;
            }

            [$isCorrect, $awarded] = $this->gradeObjective($question, $answer, $points);
            $answer->update([
                'is_correct' => $isCorrect,
                'points_awarded' => $awarded,
            ]);

            $score += $awarded;
        }

        $attempt->update([
            'score' => $score,
            'max_score' => $maxScore,
        ]);
    }

    /**
     * تصحيح يدوي لسؤال مقالي.
     */
    public function gradeEssay(User $teacher, ExamAttempt $attempt, Question $question, float $pointsAwarded): ExamAnswer
    {
        $attempt->loadMissing('exam.subject');
        $this->access->assertTeacherOwnsSubject($teacher, $attempt->exam->subject);

        if ($question->type !== QuestionType::Essay) {
            throw ValidationException::withMessages([
                'question' => 'التصحيح اليدوي للأسئلة المقالية فقط.',
            ]);
        }

        $answer = ExamAnswer::query()
            ->where('attempt_id', $attempt->id)
            ->where('question_id', $question->id)
            ->firstOrFail();

        $max = (float) ($attempt->exam->questions()->where('questions.id', $question->id)->first()?->pivot?->points ?? $question->points);
        $pointsAwarded = max(0, min($max, $pointsAwarded));

        $answer->update([
            'points_awarded' => $pointsAwarded,
            'is_correct' => $pointsAwarded >= $max,
        ]);

        $this->recalculateScore($attempt);

        return $answer->refresh();
    }

    private function recalculateScore(ExamAttempt $attempt): void
    {
        $attempt->load('answers');
        $score = (float) $attempt->answers->sum('points_awarded');
        $attempt->update(['score' => $score]);
    }

    /**
     * @return array{0: bool, 1: float}
     */
    private function gradeObjective(Question $question, ExamAnswer $answer, float $points): array
    {
        return match ($question->type) {
            QuestionType::Mcq, QuestionType::TrueFalse => $this->gradeByOption($question, $answer, $points),
            QuestionType::FillBlank => $this->gradeFillBlank($question, $answer, $points),
            QuestionType::Essay => [false, 0.0],
        };
    }

    /**
     * @return array{0: bool, 1: float}
     */
    private function gradeByOption(Question $question, ExamAnswer $answer, float $points): array
    {
        $correct = $question->options->firstWhere('is_correct', true);
        $isCorrect = $correct && $answer->selected_option_id === $correct->id;

        return [$isCorrect, $isCorrect ? $points : 0.0];
    }

    /**
     * @return array{0: bool, 1: float}
     */
    private function gradeFillBlank(Question $question, ExamAnswer $answer, float $points): array
    {
        $expected = mb_strtolower(trim((string) $question->correct_answer));
        $given = mb_strtolower(trim((string) $answer->answer_text));
        $isCorrect = $expected !== '' && $expected === $given;

        return [$isCorrect, $isCorrect ? $points : 0.0];
    }

    private function assertOwnedInProgress(User $student, ExamAttempt $attempt): void
    {
        $this->assertOwnedAttempt($student, $attempt);

        if (! $attempt->isInProgress()) {
            throw ValidationException::withMessages([
                'attempt' => 'المحاولة ليست جارية.',
            ]);
        }
    }

    private function assertOwnedAttempt(User $student, ExamAttempt $attempt): void
    {
        if ($attempt->student_id !== $student->id) {
            throw ValidationException::withMessages([
                'attempt' => 'غير مصرح.',
            ]);
        }
    }
}
