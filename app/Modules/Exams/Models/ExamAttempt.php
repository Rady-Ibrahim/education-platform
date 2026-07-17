<?php

namespace App\Modules\Exams\Models;

use App\Enums\ExamAttemptStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamAttempt extends Model
{
    protected $fillable = [
        'exam_id',
        'student_id',
        'status',
        'started_at',
        'submitted_at',
        'score',
        'max_score',
    ];

    protected function casts(): array
    {
        return [
            'status' => ExamAttemptStatus::class,
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'score' => 'decimal:2',
            'max_score' => 'decimal:2',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class, 'attempt_id');
    }

    public function isInProgress(): bool
    {
        return $this->status === ExamAttemptStatus::InProgress;
    }

    public function hasExpired(): bool
    {
        $duration = $this->exam?->duration_minutes;

        if (! $duration) {
            return false;
        }

        return $this->started_at->copy()->addMinutes($duration)->isPast();
    }
}
