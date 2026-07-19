<?php

namespace App\Modules\Academic\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupAttendanceSession extends Model
{
    protected $fillable = [
        'group_id',
        'session_date',
        'note',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(TeacherGroup::class, 'group_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function records(): HasMany
    {
        return $this->hasMany(GroupAttendanceRecord::class, 'session_id');
    }
}
