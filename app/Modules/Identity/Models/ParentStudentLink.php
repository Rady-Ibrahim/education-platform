<?php

namespace App\Modules\Identity\Models;

use App\Enums\ParentLinkStatus;
use App\Enums\ParentRelationship;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentStudentLink extends Model
{
    protected $table = 'parent_student';

    protected $fillable = [
        'parent_id',
        'student_id',
        'status',
        'relationship',
        'linked_by',
        'approved_by',
        'approved_at',
        'message',
    ];

    protected function casts(): array
    {
        return [
            'status' => ParentLinkStatus::class,
            'relationship' => ParentRelationship::class,
            'approved_at' => 'datetime',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function linker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'linked_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->status === ParentLinkStatus::Pending;
    }

    public function isActive(): bool
    {
        return $this->status === ParentLinkStatus::Active;
    }
}
