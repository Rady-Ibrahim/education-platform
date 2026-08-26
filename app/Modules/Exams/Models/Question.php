<?php

namespace App\Modules\Exams\Models;

use App\Enums\QuestionType;
use App\Models\User;
use App\Modules\Academic\Models\Subject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'subject_id',
        'created_by',
        'type',
        'stem',
        'points',
        'correct_answer',
        'explanation',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'points' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('ordering');
    }

    public function exams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class, 'exam_questions')
            ->withPivot(['ordering', 'points'])
            ->withTimestamps();
    }

    /**
     * Payload آمن للطالب أثناء الامتحان — بدون إجابات صحيحة.
     *
     * @return array<string, mixed>
     */
    public function toStudentArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'stem' => $this->stem,
            'points' => (float) $this->points,
            'options' => $this->options->map(fn (QuestionOption $option) => [
                'id' => $option->id,
                'label' => $option->label,
                'ordering' => $option->ordering,
            ])->values()->all(),
        ];
    }
}
