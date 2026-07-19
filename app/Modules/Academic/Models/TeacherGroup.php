<?php

namespace App\Modules\Academic\Models;

use App\Enums\GroupMembershipStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeacherGroup extends Model
{
    use SoftDeletes;

    protected $table = 'teacher_groups';

    protected $fillable = [
        'teacher_id',
        'subject_id',
        'grade_id',
        'name',
        'schedule_note',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'teacher_group_student', 'group_id', 'student_id')
            ->withPivot(['status', 'joined_at', 'left_at'])
            ->withTimestamps();
    }

    public function activeStudents(): BelongsToMany
    {
        return $this->students()->wherePivot('status', GroupMembershipStatus::Active->value);
    }

    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(GroupAttendanceSession::class, 'group_id');
    }

    public function displayLabel(): string
    {
        $grade = $this->grade?->name;
        $schedule = $this->schedule_note ? ' — '.$this->schedule_note : '';

        return ($grade ? $grade.' / ' : '').$this->name.$schedule;
    }
}
