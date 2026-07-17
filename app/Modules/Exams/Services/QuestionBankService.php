<?php

namespace App\Modules\Exams\Services;

use App\Enums\QuestionType;
use App\Models\User;
use App\Modules\Academic\Models\Subject;
use App\Modules\Content\Services\ContentAccessService;
use App\Modules\Exams\Models\Question;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuestionBankService
{
    public function __construct(
        private readonly ContentAccessService $access,
    ) {}

    /**
     * @param  array{type: string, stem: string, points?: float|int, correct_answer?: string|null, explanation?: string|null, options?: list<array{label: string, is_correct?: bool}>}  $data
     */
    public function create(User $teacher, Subject $subject, array $data): Question
    {
        $this->access->assertTeacherOwnsSubject($teacher, $subject);

        $type = QuestionType::from($data['type']);
        $this->validateQuestionPayload($type, $data);

        return DB::transaction(function () use ($teacher, $subject, $data, $type) {
            $question = Question::query()->create([
                'subject_id' => $subject->id,
                'created_by' => $teacher->id,
                'type' => $type,
                'stem' => $data['stem'],
                'points' => $data['points'] ?? 1,
                'correct_answer' => $data['correct_answer'] ?? null,
                'explanation' => $data['explanation'] ?? null,
                'is_active' => true,
            ]);

            if ($type === QuestionType::Mcq) {
                foreach (array_values($data['options']) as $index => $option) {
                    $question->options()->create([
                        'label' => $option['label'],
                        'is_correct' => (bool) ($option['is_correct'] ?? false),
                        'ordering' => $index + 1,
                    ]);
                }
            }

            if ($type === QuestionType::TrueFalse) {
                $question->options()->create(['label' => 'صح', 'is_correct' => ($data['correct_answer'] ?? '') === 'true', 'ordering' => 1]);
                $question->options()->create(['label' => 'خطأ', 'is_correct' => ($data['correct_answer'] ?? '') === 'false', 'ordering' => 2]);
            }

            return $question->load('options');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateQuestionPayload(QuestionType $type, array $data): void
    {
        if ($type === QuestionType::Mcq) {
            $options = $data['options'] ?? [];
            if (count($options) < 2) {
                throw ValidationException::withMessages([
                    'options' => 'سؤال الاختيار يحتاج خيارين على الأقل.',
                ]);
            }

            $correctCount = collect($options)->where('is_correct', true)->count();
            if ($correctCount !== 1) {
                throw ValidationException::withMessages([
                    'options' => 'يجب تحديد إجابة صحيحة واحدة فقط.',
                ]);
            }
        }

        if (in_array($type, [QuestionType::TrueFalse, QuestionType::FillBlank], true) && blank($data['correct_answer'] ?? null)) {
            throw ValidationException::withMessages([
                'correct_answer' => 'الإجابة الصحيحة مطلوبة.',
            ]);
        }
    }
}
