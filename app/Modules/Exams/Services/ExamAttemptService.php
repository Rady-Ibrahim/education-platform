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
use App\Modules\Notifications\Services\NotificationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class ExamAttemptService
{
    public function __construct(
        private readonly ContentAccessService $access,
        private readonly CertificateService $certificates,
        private readonly NotificationService $notifications,
    ) {}

    public function start(User $student, Exam $exam): ExamAttempt
    {
        $exam->loadMissing('subject');

        if ($exam->isPaper()) {
            throw ValidationException::withMessages([
                'exam' => 'هذا امتحان ورقي — الدرجة تُسجَّل يدويًا من المدرس.',
            ]);
        }

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
        $this->assertRateLimit($student, 'autosave');
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
        $this->assertRateLimit($student, 'submit');
        $this->assertOwnedAttempt($student, $attempt);

        return $this->finalize($attempt);
    }

    /**
     * تسليم نهائي (يدوي أو تلقائي عند انتهاء الوقت) — بدون تحقق هوية.
     */
    private function finalize(ExamAttempt $attempt): ExamAttempt
    {
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
        $this->notifications->notifyExamGraded($graded);

        return $graded;
    }

    /**
     * تسليم محاولة واحدة انتهى وقتها (تُستخدم من حذف الامتحان أو المجدول).
     */
    public function autoSubmitAttempt(ExamAttempt $attempt): ExamAttempt
    {
        return $this->finalize($attempt);
    }

    /**
     * تسليم كل المحاولات الجارية التي انتهى وقتها (للمجدول).
     */
    public function autoSubmitExpiredAttempts(int $limit = 200): int
    {
        $attempts = ExamAttempt::query()
            ->where('status', ExamAttemptStatus::InProgress)
            ->with('exam')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->filter(fn (ExamAttempt $attempt) => $attempt->hasExpired());

        $count = 0;

        foreach ($attempts as $attempt) {
            $this->finalize($attempt);
            $count++;
        }

        return $count;
    }

    /**
     * أسئلة المحاولة بدون تسريب الإجابات الصحيحة.
     * الترتيب ثابت لكل محاولة (يصمد أمام الـrefresh) عبر shuffle حتمي مبني على attempt id.
     *
     * @return list<array<string, mixed>>
     */
    public function questionsForStudent(ExamAttempt $attempt): array
    {
        $questions = $attempt->exam->questions()->with('options')->get();
        $shuffle = $attempt->exam->shuffle_questions;

        if ($shuffle) {
            $questions = $this->stableShuffle($questions, $attempt->id);
        }

        return $questions->map(function (Question $question) use ($attempt, $shuffle) {
            $payload = $question->toStudentArray();
            if ($shuffle) {
                $payload['options'] = $this->stableShuffle(collect($payload['options']), $attempt->id * 31 + $question->id)->all();
            }

            return $payload;
        })->all();
    }

    /**
     * الوقت المتبقي بالثواني من قاعدة البيانات (started_at + duration) — null إن لم يكن مؤقت.
     */
    public function remainingSeconds(ExamAttempt $attempt): ?int
    {
        $duration = $attempt->exam->duration_minutes;

        if (! $duration || ! $attempt->isInProgress()) {
            return null;
        }

        $endsAt = $attempt->started_at->copy()->addMinutes($duration);

        return (int) max(0, now()->diffInSeconds($endsAt, false));
    }

    /**
     * ملخص نتيجة المحاولة للعرض.
     *
     * @return array{total: int, answered: int, correct: int, wrong: int, unanswered: int, score: float, max_score: float, percent: float, passed: bool|null}
     */
    public function resultSummary(ExamAttempt $attempt): array
    {
        $attempt->loadMissing(['answers', 'exam.questions']);

        $total = $attempt->exam->questions->count();
        $answered = 0;
        $correct = 0;
        $wrong = 0;

        foreach ($attempt->exam->questions as $question) {
            $answer = $attempt->answers->firstWhere('question_id', $question->id);

            if (! $answer || (blank($answer->selected_option_id) && blank($answer->answer_text))) {
                continue;
            }

            $answered++;

            if ($answer->is_correct === true) {
                $correct++;
            } elseif ($answer->is_correct === false) {
                $wrong++;
            }
        }

        $score = (float) ($attempt->score ?? 0);
        $maxScore = (float) ($attempt->max_score ?? 0);
        $percent = $maxScore > 0 ? round($score / $maxScore * 100, 2) : 0.0;

        return [
            'total' => $total,
            'answered' => $answered,
            'correct' => $correct,
            'wrong' => $wrong,
            'unanswered' => max(0, $total - $answered),
            'score' => $score,
            'max_score' => $maxScore,
            'percent' => $percent,
            'passed' => $attempt->exam->pass_score !== null ? $percent >= (float) $attempt->exam->pass_score : null,
        ];
    }

    /**
     * Fisher-Yates بمولد عشوائي حتمي (LCG) — نفس الـseed يعطي نفس الترتيب دائمًا.
     *
     * @template T
     *
     * @param  Collection<int, T>  $items
     * @return Collection<int, T>
     */
    private function stableShuffle(Collection $items, int $seed): Collection
    {
        $indices = $items->keys()->values()->all();
        $state = $seed % 2147483647;

        if ($state <= 0) {
            $state += 2147483646;
        }

        for ($i = count($indices) - 1; $i > 0; $i--) {
            $state = ($state * 48271) % 2147483647;
            $j = $state % ($i + 1);
            [$indices[$i], $indices[$j]] = [$indices[$j], $indices[$i]];
        }

        return collect($indices)->map(fn (int $index) => $items[$index])->values();
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
     * تسجيل درجة يدوية لامتحان ورقي (أو تعديل درجة يدوية).
     */
    public function recordManualScore(User $teacher, Exam $exam, User $student, float $score, ?string $notes = null): ExamAttempt
    {
        $exam->loadMissing('subject');
        $this->access->assertTeacherOwnsSubject($teacher, $exam->subject);

        if (! $exam->isPaper()) {
            throw ValidationException::withMessages([
                'exam' => 'التسجيل اليدوي للدرجة متاح للامتحانات الورقية فقط.',
            ]);
        }

        if (! $teacher->students()->where('users.id', $student->id)->exists()) {
            throw ValidationException::withMessages([
                'student' => 'الطالب غير مرتبط بك.',
            ]);
        }

        $maxScore = (float) ($exam->manual_max_score ?: 0);
        if ($maxScore <= 0) {
            throw ValidationException::withMessages([
                'exam' => 'الامتحان بدون درجة نهائية محددة.',
            ]);
        }

        $score = max(0, min($maxScore, $score));

        $attempt = ExamAttempt::query()->updateOrCreate(
            [
                'exam_id' => $exam->id,
                'student_id' => $student->id,
            ],
            [
                'status' => ExamAttemptStatus::Graded,
                'started_at' => now(),
                'submitted_at' => now(),
                'score' => $score,
                'max_score' => $maxScore,
            ]
        );

        $this->certificates->issueForPassedAttempt($attempt->fresh(['exam', 'student']));
        $this->notifications->notifyExamGraded($attempt->refresh());

        return $attempt->refresh();
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

        // إشعار الطالب وولي الأمر بالنتيجة النهائية بعد اكتمال التصحيح.
        $this->notifications->notifyExamGraded($attempt->refresh());

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

    private function assertRateLimit(User $student, string $action): void
    {
        $max = (int) config("exams.{$action}_rate_limit", $action === 'submit' ? 10 : 60);
        $decay = (int) config("exams.{$action}_rate_decay_seconds", 60);
        $key = "exam-{$action}:{$student->id}";

        if (RateLimiter::tooManyAttempts($key, $max)) {
            throw ValidationException::withMessages([
                'rate' => 'تم تجاوز الحد المسموح من الطلبات. حاول بعد لحظات.',
            ]);
        }

        RateLimiter::hit($key, $decay);
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
