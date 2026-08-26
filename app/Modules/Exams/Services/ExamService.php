<?php

namespace App\Modules\Exams\Services;

use App\Enums\ExamAttemptStatus;
use App\Enums\ExamDeliveryMode;
use App\Models\User;
use App\Modules\Academic\Models\Subject;
use App\Modules\Content\Services\ContentAccessService;
use App\Modules\Exams\Models\Exam;
use App\Modules\Exams\Models\ExamAttempt;
use App\Modules\Exams\Models\Question;
use App\Modules\Notifications\Services\NotificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamService
{
    public function __construct(
        private readonly ContentAccessService $access,
        private readonly NotificationService $notifications,
        private readonly ExamAttemptService $attempts,
    ) {}

    /**
     * @param  array{title: string, description?: string|null, duration_minutes?: int|null, max_attempts?: int, pass_score?: float|null, shuffle_questions?: bool, is_published?: bool, starts_at?: string|null, ends_at?: string|null, question_ids?: list<int>, delivery_mode?: string, manual_max_score?: float|null, paper?: UploadedFile|null}  $data
     */
    public function create(User $teacher, Subject $subject, array $data): Exam
    {
        $this->access->assertTeacherOwnsSubject($teacher, $subject);

        $mode = ExamDeliveryMode::from($data['delivery_mode'] ?? ExamDeliveryMode::Online->value);

        if (
            $mode === ExamDeliveryMode::Online
            && ($data['is_published'] ?? false)
            && empty($data['question_ids'])
        ) {
            throw ValidationException::withMessages([
                'question_ids' => 'الامتحان الإلكتروني يحتاج سؤال واحد على الأقل.',
            ]);
        }

        if ($mode === ExamDeliveryMode::Paper && empty($data['manual_max_score'])) {
            throw ValidationException::withMessages([
                'manual_max_score' => 'حدّد الدرجة النهائية للامتحان الورقي.',
            ]);
        }

        return DB::transaction(function () use ($teacher, $subject, $data, $mode) {
            $paperMeta = $this->storePaperFile($data['paper'] ?? null);

            $exam = Exam::query()->create([
                'subject_id' => $subject->id,
                'created_by' => $teacher->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'duration_minutes' => $mode === ExamDeliveryMode::Paper ? null : ($data['duration_minutes'] ?? null),
                'max_attempts' => $data['max_attempts'] ?? 1,
                'pass_score' => $data['pass_score'] ?? null,
                'shuffle_questions' => $mode === ExamDeliveryMode::Paper ? false : ($data['shuffle_questions'] ?? true),
                'is_published' => $data['is_published'] ?? false,
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'delivery_mode' => $mode,
                'manual_max_score' => $mode === ExamDeliveryMode::Paper ? $data['manual_max_score'] : null,
                'paper_path' => $paperMeta['path'] ?? null,
                'paper_disk' => $paperMeta['disk'] ?? null,
                'paper_original_name' => $paperMeta['name'] ?? null,
            ]);

            if ($mode === ExamDeliveryMode::Online && ! empty($data['question_ids'])) {
                $this->syncQuestions($teacher, $exam, $data['question_ids']);
            }

            $exam = $exam->load('questions');

            if ($exam->is_published) {
                $this->notifications->notifyExamPublished($exam);
            }

            return $exam;
        });
    }

    /**
     * @return array{path?: string, disk?: string, name?: string}
     */
    private function storePaperFile(?UploadedFile $file): array
    {
        if (! $file) {
            return [];
        }

        $disk = 'public';
        $path = $file->store('exam-papers', $disk);

        return [
            'path' => $path,
            'disk' => $disk,
            'name' => $file->getClientOriginalName(),
        ];
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

    /**
     * تحديث بيانات امتحان (عنوان/وصف/مدة/نافذة/درجة نجاح/محاولات) مع مزامنة اختيارية للأسئلة.
     *
     * @param  array{title?: string, description?: string|null, duration_minutes?: int|null, max_attempts?: int, pass_score?: float|null, shuffle_questions?: bool, starts_at?: string|null, ends_at?: string|null, question_ids?: list<int>}  $data
     */
    public function update(User $teacher, Exam $exam, array $data): Exam
    {
        $this->access->assertTeacherOwnsSubject($teacher, $exam->subject);

        if ($exam->isPaper()) {
            throw ValidationException::withMessages([
                'exam' => 'الامتحانات الورقية تُدار من صفحة الدرجات اليدوية.',
            ]);
        }

        return DB::transaction(function () use ($teacher, $exam, $data) {
            $exam->update([
                'title' => $data['title'] ?? $exam->title,
                'description' => array_key_exists('description', $data) ? $data['description'] : $exam->description,
                'duration_minutes' => $data['duration_minutes'] ?? $exam->duration_minutes,
                'max_attempts' => $data['max_attempts'] ?? $exam->max_attempts,
                'pass_score' => array_key_exists('pass_score', $data) ? $data['pass_score'] : $exam->pass_score,
                'shuffle_questions' => array_key_exists('shuffle_questions', $data)
                    ? (bool) $data['shuffle_questions']
                    : $exam->shuffle_questions,
                'starts_at' => array_key_exists('starts_at', $data) ? ($data['starts_at'] ?: null) : $exam->starts_at,
                'ends_at' => array_key_exists('ends_at', $data) ? ($data['ends_at'] ?: null) : $exam->ends_at,
            ]);

            if (! empty($data['question_ids'])) {
                $this->syncQuestions($teacher, $exam, $data['question_ids']);
            }

            return $exam->refresh()->load('questions');
        });
    }

    /**
     * حذف الامتحان (soft delete) — تسليم المحاولات الجارية أولًا حفاظًا على بيانات الطلاب.
     */
    public function delete(User $teacher, Exam $exam): void
    {
        $this->access->assertTeacherOwnsSubject($teacher, $exam->subject);

        DB::transaction(function () use ($exam) {
            $exam->attempts()
                ->where('status', ExamAttemptStatus::InProgress)
                ->get()
                ->each(fn (ExamAttempt $attempt) => $this->attempts->autoSubmitAttempt($attempt));

            $exam->update(['is_published' => false]);
            $exam->delete();
        });
    }

    public function publish(User $teacher, Exam $exam, bool $published = true): Exam
    {
        $this->access->assertTeacherOwnsSubject($teacher, $exam->subject);

        if ($published && ! $exam->isPaper() && $exam->questions()->count() === 0) {
            throw ValidationException::withMessages([
                'exam' => 'لا يمكن نشر امتحان إلكتروني بدون أسئلة.',
            ]);
        }

        if ($published && $exam->isPaper() && ! $exam->manual_max_score) {
            throw ValidationException::withMessages([
                'exam' => 'الامتحان الورقي يحتاج درجة نهائية.',
            ]);
        }

        $wasPublished = $exam->is_published;
        $exam->update(['is_published' => $published]);
        $exam = $exam->refresh();

        if ($published && ! $wasPublished) {
            $this->notifications->notifyExamPublished($exam);
        }

        return $exam;
    }
}
