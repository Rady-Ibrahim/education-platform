<?php

namespace App\Modules\Payments\Models;

use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Models\User;
use App\Modules\Academic\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_id',
        'teacher_id',
        'subscription_id',
        'branch_id',
        'channel',
        'provider',
        'amount',
        'external_reference',
        'proof_path',
        'status',
        'recorded_by',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'channel' => PaymentChannel::class,
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
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

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::PendingReview;
    }
}
