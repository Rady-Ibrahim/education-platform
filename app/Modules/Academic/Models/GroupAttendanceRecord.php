<?php

namespace App\Modules\Academic\Models;

use App\Enums\AttendanceStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupAttendanceRecord extends Model
{
    protected $fillable = [
        'session_id',
        'student_id',
        'status',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'status' => AttendanceStatus::class,
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(GroupAttendanceSession::class, 'session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
