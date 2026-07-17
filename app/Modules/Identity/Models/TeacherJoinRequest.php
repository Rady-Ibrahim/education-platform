<?php

namespace App\Modules\Identity\Models;

use App\Enums\JoinRequestStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeacherJoinRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_id',
        'teacher_id',
        'status',
        'message',
        'review_note',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => JoinRequestStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function isPending(): bool
    {
        return $this->status === JoinRequestStatus::Pending;
    }
}
