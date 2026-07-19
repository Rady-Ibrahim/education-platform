<?php

namespace App\Modules\Exams\Models;

use App\Enums\ExamDeliveryMode;
use App\Models\User;
use App\Modules\Academic\Models\Subject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exam extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'subject_id',
        'created_by',
        'title',
        'description',
        'duration_minutes',
        'max_attempts',
        'pass_score',
        'shuffle_questions',
        'is_published',
        'starts_at',
        'ends_at',
        'delivery_mode',
        'manual_max_score',
        'paper_path',
        'paper_disk',
        'paper_original_name',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'max_attempts' => 'integer',
            'pass_score' => 'decimal:2',
            'shuffle_questions' => 'boolean',
            'is_published' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'delivery_mode' => ExamDeliveryMode::class,
            'manual_max_score' => 'decimal:2',
        ];
    }

    public function isPaper(): bool
    {
        return $this->delivery_mode === ExamDeliveryMode::Paper;
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'exam_questions')
            ->withPivot(['ordering', 'points'])
            ->withTimestamps()
            ->orderByPivot('ordering');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function isAvailableNow(): bool
    {
        if (! $this->is_published) {
            return false;
        }

        if ($this->isPaper()) {
            return true;
        }

        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }
}
