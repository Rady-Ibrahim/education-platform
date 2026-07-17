<?php

namespace App\Modules\Payments\Models;

use App\Enums\PlatformSubscriptionStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlatformSubscription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'teacher_id',
        'status',
        'trial_ends_at',
        'current_period_ends_at',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'status' => PlatformSubscriptionStatus::class,
            'trial_ends_at' => 'datetime',
            'current_period_ends_at' => 'datetime',
            'amount' => 'decimal:2',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PlatformPayment::class);
    }

    public function isInTrial(): bool
    {
        return $this->status === PlatformSubscriptionStatus::Trialing
            && $this->trial_ends_at
            && $this->trial_ends_at->isFuture();
    }

    public function allowsAccess(): bool
    {
        if ($this->status === PlatformSubscriptionStatus::Trialing) {
            return $this->trial_ends_at === null || $this->trial_ends_at->isFuture();
        }

        if ($this->status === PlatformSubscriptionStatus::Active) {
            return $this->current_period_ends_at === null || $this->current_period_ends_at->isFuture();
        }

        // Allow limited access while payment pending review
        return $this->status === PlatformSubscriptionStatus::PendingPayment;
    }
}
