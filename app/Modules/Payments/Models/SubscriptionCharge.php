<?php

namespace App\Modules\Payments\Models;

use App\Enums\ChargeStatus;
use App\Enums\PaymentStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionCharge extends Model
{
    protected $fillable = [
        'subscription_id',
        'student_id',
        'teacher_id',
        'billing_month',
        'expected_amount',
        'discount_amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'billing_month' => 'date',
            'expected_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'status' => ChargeStatus::class,
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'subscription_charge_id');
    }

    public function paidAmount(): float
    {
        return (float) $this->payments()
            ->where('status', PaymentStatus::Confirmed)
            ->sum('amount');
    }

    public function netExpected(): float
    {
        return max(0, (float) $this->expected_amount - (float) $this->discount_amount);
    }

    public function remainingAmount(): float
    {
        return max(0, round($this->netExpected() - $this->paidAmount(), 2));
    }

    public function monthLabel(): string
    {
        return $this->billing_month?->locale('ar')->translatedFormat('F Y')
            ?? $this->billing_month?->format('Y-m')
            ?? '—';
    }
}
