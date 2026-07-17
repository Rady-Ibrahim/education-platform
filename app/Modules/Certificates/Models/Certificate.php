<?php

namespace App\Modules\Certificates\Models;

use App\Models\User;
use App\Modules\Academic\Models\Subject;
use App\Modules\Exams\Models\Exam;
use App\Modules\Exams\Models\ExamAttempt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Certificate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_id',
        'exam_id',
        'exam_attempt_id',
        'subject_id',
        'issued_by',
        'title',
        'verification_code',
        'score',
        'max_score',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'issued_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function verifyUrl(): string
    {
        return route('certificates.verify', $this->verification_code);
    }

    public function scorePercent(): ?float
    {
        if (! $this->max_score || (float) $this->max_score <= 0) {
            return null;
        }

        return round(((float) $this->score / (float) $this->max_score) * 100, 1);
    }
}
