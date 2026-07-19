<?php

namespace App\Modules\Payments\Models;

use App\Enums\ChargeStatus;
use App\Enums\FeeCategory;
use App\Enums\PaymentStatus;
use App\Models\User;
use App\Modules\Academic\Models\Subject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentFee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'teacher_id',
        'student_id',
        'subject_id',
        'created_by',
        'title',
        'category',
        'expected_amount',
        'discount_amount',
        'status',
        'due_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'category' => FeeCategory::class,
            'status' => ChargeStatus::class,
            'expected_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'student_fee_id');
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
}
