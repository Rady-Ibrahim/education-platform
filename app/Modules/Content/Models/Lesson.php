<?php

namespace App\Modules\Content\Models;

use App\Enums\LessonType;
use App\Models\User;
use App\Modules\Academic\Models\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lesson extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'unit_id',
        'created_by',
        'title',
        'type',
        'body',
        'bunny_video_id',
        'ordering',
        'duration_seconds',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'type' => LessonType::class,
            'ordering' => 'integer',
            'duration_seconds' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(LessonAttachment::class);
    }

    public function progressRecords(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function progressFor(User $student): HasOne
    {
        return $this->hasOne(LessonProgress::class)->where('student_id', $student->id);
    }

    public function hasVideo(): bool
    {
        return filled($this->bunny_video_id);
    }
}
