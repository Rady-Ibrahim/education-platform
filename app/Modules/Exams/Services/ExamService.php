<?php

namespace App\Modules\Exams\Services;

use App\Models\User;
use App\Modules\Academic\Models\Subject;
use App\Modules\Content\Services\ContentAccessService;
use App\Modules\Exams\Models\Exam;
use App\Modules\Exams\Models\Question;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamService
{
    public function __construct(
        private readonly ContentAccessService $access,
    ) {}

    /**
     * @param  array{title: string, description?: string|null, duration_minutes?: int|null, max_attempts?: int, pass_score?: float|null, shuffle_questions?: bool, is_published?: bool, starts_at?: string|null, ends_at?: string|null, question_ids?: list<int>}  $data
     */
    public function create(User $teacher, Subject $subject, array $data): Exam
    {
        $this->access->assertTeacherOwnsSubject($teacher, $subject);

        return DB::transaction(function () use ($teacher, $subject, $data) {
            $exam = Exam::query()->create([
                'subject_id' => $subject->id,
                'created_by' => $teacher->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'duration_minutes' => $data['duration_minutes'] ?? null,
                'max_attempts' => $data['max_attempts'] ?? 1,
                'pass_score' => $data['pass_score'] ?? null,
                'shuffle_questions' => $data['shuffle_questions'] ?? true,
                'is_published' => $data['is_published'] ?? false,
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
            ]);

            if (! empty($data['question_ids'])) {
                $this->syncQuestions($teacher, $exam, $data['question_ids']);
            }

            return $exam->load('questions');
        });
    }

    /**
     * @param  list<int>  $questionIds
     */
    public function syncQuestions(User $teacher, Exam $exam, array $questionIds): Exam
    {
        $this->access->assertTeacherOwnsSubject($teacher, $exam->subject);

        $questions = Question::query()
            ->whereIn('id', $questionIds)
            ->where('subject_id', $exam->subject_id)
            ->where('is_active', true)
            ->get();

        if ($questions->count() !== count(array_unique($questionIds))) {
            throw ValidationException::withMessages([
                'question_ids' => 'بعض الأسئلة غير صالحة أو لا تتبع نفس المادة.',
            ]);
        }

        $sync = [];
        foreach (array_values($questionIds) as $index => $questionId) {
            $question = $questions->firstWhere('id', $questionId);
            $sync[$questionId] = [
                'ordering' => $index + 1,
                'points' => $question->points,
            ];
        }

        $exam->questions()->sync($sync);

        return $exam->refresh()->load('questions');
    }

    public function publish(User $teacher, Exam $exam, bool $published = true): Exam
    {
        $this->access->assertTeacherOwnsSubject($teacher, $exam->subject);

        if ($published && $exam->questions()->count() === 0) {
            throw ValidationException::withMessages([
                'exam' => 'لا يمكن نشر امتحان بدون أسئلة.',
            ]);
        }

        $exam->update(['is_published' => $published]);

        return $exam->refresh();
    }
}
