<?php

namespace App\Modules\Academic\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Grade extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'stage_id',
        'name',
        'code',
        'ordering',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'ordering' => 'integer',
        ];
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class)->orderBy('ordering');
    }

    public function teacherGroups(): HasMany
    {
        return $this->hasMany(TeacherGroup::class, 'grade_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'student_grade', 'grade_id', 'student_id')
            ->withPivot('enrolled_at')
            ->withTimestamps();
    }
}
