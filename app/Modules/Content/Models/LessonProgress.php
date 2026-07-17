<?php

namespace App\Modules\Content\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonProgress extends Model
{
    protected $table = 'lesson_progress';

    protected $fillable = [
        'lesson_id',
        'student_id',
        'percent',
        'watched_seconds',
        'is_completed',
        'last_watched_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'percent' => 'integer',
            'watched_seconds' => 'integer',
            'is_completed' => 'boolean',
            'last_watched_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
