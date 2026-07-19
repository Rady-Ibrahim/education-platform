<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Modules\Academic\Models\Branch;
use App\Modules\Academic\Models\Grade;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\TeacherGroup;
use App\Modules\Identity\Models\ParentStudentLink;
use App\Modules\Identity\Models\TeacherJoinRequest;
use App\Modules\Payments\Models\Subscription;
use App\Modules\Payments\Models\SubscriptionPlan;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'headline',
        'bio',
        'avatar_path',
        'cover_path',
        'is_publicly_visible',
        'email',
        'phone',
        'vodafone_cash_number',
        'payment_instructions',
        'student_code',
        'branch_id',
        'created_by',
        'status',
        'approved_at',
        'approved_by',
        'rejection_reason',
        'password',
        'email_verified_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'is_publicly_visible' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    public function isPublicTeacher(): bool
    {
        return $this->hasRole(UserRole::Teacher)
            && $this->isActive()
            && $this->is_publicly_visible
            && filled($this->slug);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(self::class, 'approved_by');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'teacher_student', 'teacher_id', 'student_id')
            ->withPivot('joined_at')
            ->withTimestamps();
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'teacher_student', 'student_id', 'teacher_id')
            ->withPivot('joined_at')
            ->withTimestamps();
    }

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'parent_student', 'parent_id', 'student_id')
            ->withPivot(['status', 'relationship', 'linked_by', 'approved_by', 'approved_at', 'message'])
            ->withTimestamps();
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'parent_student', 'student_id', 'parent_id')
            ->withPivot(['status', 'relationship', 'linked_by', 'approved_by', 'approved_at', 'message'])
            ->withTimestamps();
    }

    public function parentLinksAsParent(): HasMany
    {
        return $this->hasMany(ParentStudentLink::class, 'parent_id');
    }

    public function parentLinksAsStudent(): HasMany
    {
        return $this->hasMany(ParentStudentLink::class, 'student_id');
    }

    public function joinRequestsAsStudent(): HasMany
    {
        return $this->hasMany(TeacherJoinRequest::class, 'student_id');
    }

    public function joinRequestsAsTeacher(): HasMany
    {
        return $this->hasMany(TeacherJoinRequest::class, 'teacher_id');
    }

    public function teachingSubjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'teacher_subject', 'teacher_id', 'subject_id')
            ->withTimestamps();
    }

    public function teacherGroups(): HasMany
    {
        return $this->hasMany(TeacherGroup::class, 'teacher_id');
    }

    public function studentGroups(): BelongsToMany
    {
        return $this->belongsToMany(TeacherGroup::class, 'teacher_group_student', 'student_id', 'group_id')
            ->withPivot(['status', 'joined_at', 'left_at'])
            ->withTimestamps();
    }

    public function subscriptionPlans(): HasMany
    {
        return $this->hasMany(SubscriptionPlan::class, 'teacher_id');
    }

    public function coverUrl(): ?string
    {
        return $this->publicAssetUrl($this->cover_path ?: $this->avatar_path);
    }

    public function avatarUrl(): ?string
    {
        return $this->publicAssetUrl($this->avatar_path ?: $this->cover_path);
    }

    private function publicAssetUrl(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset(ltrim($path, '/'));
    }

    public function grades(): BelongsToMany
    {
        return $this->belongsToMany(Grade::class, 'student_grade', 'student_id', 'grade_id')
            ->withPivot('enrolled_at')
            ->withTimestamps();
    }

    public function subscriptionsAsStudent(): HasMany
    {
        return $this->hasMany(Subscription::class, 'student_id');
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function isPendingAdmin(): bool
    {
        return $this->status === UserStatus::PendingAdmin;
    }

    public function isSuspended(): bool
    {
        return $this->status === UserStatus::Suspended;
    }

    public function primaryRole(): ?UserRole
    {
        foreach (UserRole::cases() as $role) {
            if ($this->hasRole($role->value)) {
                return $role;
            }
        }

        return null;
    }
}
