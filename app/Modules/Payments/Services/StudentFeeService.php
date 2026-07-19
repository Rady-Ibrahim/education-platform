<?php

namespace App\Modules\Payments\Services;

use App\Enums\ChargeStatus;
use App\Enums\FeeCategory;
use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Academic\Models\Branch;
use App\Modules\Identity\Services\ParentLinkService;
use App\Modules\Notifications\Services\NotificationService;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\StudentFee;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentFeeService
{
    public function __construct(
        private readonly PaymentReviewService $review,
        private readonly MonthlyCollectionService $collection,
        private readonly NotificationService $notifications,
        private readonly ParentLinkService $parentLinks,
    ) {}

    /**
     * @param  array{title: string, category?: string, expected_amount: float|int, discount_amount?: float|int, subject_id?: int|null, due_date?: string|null, notes?: string|null}  $data
     */
    public function create(User $teacher, User $student, array $data): StudentFee
    {
        $this->assertTeacherOwnsStudent($teacher, $student);

        $amount = (float) $data['expected_amount'];
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'expected_amount' => 'أدخل مبلغًا أكبر من صفر.',
            ]);
        }

        $category = FeeCategory::tryFrom($data['category'] ?? FeeCategory::Books->value)
            ?? FeeCategory::Books;

        return StudentFee::query()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'subject_id' => $data['subject_id'] ?? null,
            'created_by' => $teacher->id,
            'title' => $data['title'],
            'category' => $category,
            'expected_amount' => $amount,
            'discount_amount' => max(0, (float) ($data['discount_amount'] ?? 0)),
            'status' => ChargeStatus::Due,
            'due_date' => $data['due_date'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * @param  list<int>  $studentIds
     * @param  array{title: string, category?: string, expected_amount: float|int, discount_amount?: float|int, subject_id?: int|null, due_date?: string|null, notes?: string|null}  $data
     * @return Collection<int, StudentFee>
     */
    public function createForStudents(User $teacher, array $studentIds, array $data): Collection
    {
        $created = collect();

        foreach (array_unique($studentIds) as $studentId) {
            $student = User::query()->findOrFail($studentId);
            $created->push($this->create($teacher, $student, $data));
        }

        return $created;
    }

    /**
     * @return Collection<int, StudentFee>
     */
    public function listForTeacher(User $teacher, bool $openOnly = true, ?string $search = null): Collection
    {
        $this->assertTeacher($teacher);

        $query = StudentFee::query()
            ->with(['student', 'subject', 'payments' => fn ($q) => $q->where('status', PaymentStatus::Confirmed)])
            ->where('teacher_id', $teacher->id)
            ->when($openOnly, fn ($q) => $q->whereIn('status', [ChargeStatus::Due->value, ChargeStatus::Partial->value]))
            ->when($search, function ($q) use ($search) {
                $term = '%'.$search.'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('title', 'like', $term)
                        ->orWhereHas('student', function ($student) use ($term) {
                            $student->where('name', 'like', $term)
                                ->orWhere('student_code', 'like', $term)
                                ->orWhere('phone', 'like', $term);
                        });
                });
            })
            ->latest();

        return $query->get()->each(fn (StudentFee $fee) => $this->refreshFeeStatus($fee));
    }

    /**
     * @return Collection<int, StudentFee>
     */
    public function listOpenForStudent(User $student): Collection
    {
        return StudentFee::query()
            ->with(['teacher', 'subject'])
            ->where('student_id', $student->id)
            ->whereIn('status', [ChargeStatus::Due->value, ChargeStatus::Partial->value])
            ->latest()
            ->get()
            ->each(fn (StudentFee $fee) => $this->refreshFeeStatus($fee));
    }

    /**
     * @param  array{amount?: float|int|null, discount?: float|int|null, notes?: string|null}  $data
     */
    public function collectCash(User $teacher, StudentFee $fee, array $data = []): Payment
    {
        $this->assertTeacherOwnsFee($teacher, $fee);
        $fee->loadMissing('student');
        $this->refreshFeeStatus($fee);
        $fee = $fee->fresh();

        if (! $fee->status->isOpen()) {
            throw ValidationException::withMessages([
                'fee' => 'هذا المصروف مدفوع أو معفى بالفعل.',
            ]);
        }

        if (array_key_exists('discount', $data) && $data['discount'] !== null && $data['discount'] !== '') {
            $discount = max(0, (float) $data['discount']);
            if ($discount > (float) $fee->expected_amount) {
                throw ValidationException::withMessages([
                    'discount' => 'الخصم أكبر من قيمة المصروف.',
                ]);
            }
            $fee->update(['discount_amount' => $discount]);
            $fee = $fee->fresh();
        }

        $remaining = $fee->remainingAmount();
        if ($remaining <= 0) {
            throw ValidationException::withMessages([
                'fee' => 'لا يوجد متبقي على هذا المصروف.',
            ]);
        }

        $amount = array_key_exists('amount', $data) && $data['amount'] !== null && $data['amount'] !== ''
            ? (float) $data['amount']
            : $remaining;

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'أدخل مبلغًا أكبر من صفر.',
            ]);
        }

        if ($amount > $remaining + 0.001) {
            throw ValidationException::withMessages([
                'amount' => 'المبلغ أكبر من المتبقي ('.$remaining.' ج.م).',
            ]);
        }

        return DB::transaction(function () use ($teacher, $fee, $amount, $data) {
            $payment = Payment::query()->create([
                'student_id' => $fee->student_id,
                'teacher_id' => $fee->teacher_id,
                'student_fee_id' => $fee->id,
                'branch_id' => $fee->student?->branch_id ?? Branch::defaultBranch()?->id,
                'channel' => PaymentChannel::Cash,
                'provider' => 'manual',
                'amount' => $amount,
                'discount_amount' => 0,
                'status' => PaymentStatus::PendingReview,
                'recorded_by' => $teacher->id,
                'notes' => $data['notes'] ?? ('تحصيل كاش — '.$fee->title),
                'receipt_number' => $this->collection->nextReceiptNumber($teacher),
            ]);

            $confirmed = $this->review->confirm($teacher, $payment);
            $this->refreshFeeStatus($fee->fresh());

            return $confirmed->fresh(['studentFee']);
        });
    }

    /**
     * @param  array{amount?: float|int|null, external_reference?: string|null, notes?: string|null}  $data
     */
    public function submitVodafoneForChild(
        User $parent,
        User $student,
        StudentFee $fee,
        array $data,
        ?UploadedFile $proof = null,
    ): Payment {
        if (! $this->parentLinks->parentCanViewStudent($parent, $student)) {
            throw ValidationException::withMessages([
                'student' => 'هذا الطالب غير مرتبط بحسابك.',
            ]);
        }

        if ((int) $fee->student_id !== (int) $student->id) {
            throw ValidationException::withMessages([
                'fee' => 'المصروف لا يخص هذا الطالب.',
            ]);
        }

        $this->refreshFeeStatus($fee);
        $fee = $fee->fresh();

        if (! $fee->status->isOpen()) {
            throw ValidationException::withMessages([
                'fee' => 'هذا المصروف مدفوع بالفعل.',
            ]);
        }

        $hasPending = Payment::query()
            ->where('student_fee_id', $fee->id)
            ->where('status', PaymentStatus::PendingReview)
            ->exists();

        if ($hasPending) {
            throw ValidationException::withMessages([
                'payment' => 'يوجد إثبات دفع قيد المراجعة بالفعل لهذا المصروف.',
            ]);
        }

        if (! $proof) {
            throw ValidationException::withMessages([
                'proof' => 'صورة وصل فودافون كاش مطلوبة.',
            ]);
        }

        $remaining = $fee->remainingAmount();
        $amount = array_key_exists('amount', $data) && $data['amount']
            ? (float) $data['amount']
            : $remaining;

        if ($amount <= 0 || $amount > $remaining + 0.001) {
            throw ValidationException::withMessages([
                'amount' => 'المبلغ غير صالح (المتبقي '.$remaining.' ج.م).',
            ]);
        }

        $proofPath = $proof->store('payment-proofs', 'public');

        $payment = Payment::query()->create([
            'student_id' => $student->id,
            'teacher_id' => $fee->teacher_id,
            'student_fee_id' => $fee->id,
            'branch_id' => $student->branch_id ?? Branch::defaultBranch()?->id,
            'channel' => PaymentChannel::VodafoneCash,
            'provider' => 'manual',
            'amount' => $amount,
            'external_reference' => $data['external_reference'] ?? null,
            'proof_path' => $proofPath,
            'status' => PaymentStatus::PendingReview,
            'recorded_by' => $parent->id,
            'notes' => $data['notes'] ?? ('فودافون — '.$fee->title),
        ]);

        $this->notifications->notifyPaymentPendingReview($payment);

        return $payment;
    }

    public function refreshFeeStatus(StudentFee $fee): StudentFee
    {
        if ($fee->status === ChargeStatus::Waived) {
            return $fee;
        }

        $paid = $fee->paidAmount();
        $net = $fee->netExpected();

        $status = match (true) {
            $paid <= 0 => ChargeStatus::Due,
            $paid + 0.001 >= $net => ChargeStatus::Paid,
            default => ChargeStatus::Partial,
        };

        if ($fee->status !== $status) {
            $fee->update(['status' => $status]);
        }

        return $fee->fresh();
    }

    public function waive(User $teacher, StudentFee $fee): StudentFee
    {
        $this->assertTeacherOwnsFee($teacher, $fee);
        $fee->update(['status' => ChargeStatus::Waived]);

        return $fee->fresh();
    }

    /**
     * @return array{vodafone_cash_number: string|null, payment_instructions: string|null, teacher_name: string|null}
     */
    public function paymentInstructions(StudentFee $fee): array
    {
        $fee->loadMissing(['teacher', 'student.branch']);
        $teacher = $fee->teacher;
        $branch = $fee->student?->branch ?? Branch::defaultBranch();

        return [
            'vodafone_cash_number' => $teacher?->vodafone_cash_number ?: $branch?->vodafone_cash_number,
            'payment_instructions' => $teacher?->payment_instructions ?: $branch?->payment_instructions,
            'teacher_name' => $teacher?->name,
        ];
    }

    public function openFeesTotalForTeacher(User $teacher): float
    {
        return (float) $this->listForTeacher($teacher, openOnly: true)
            ->sum(fn (StudentFee $fee) => $fee->remainingAmount());
    }

    public function openFeesCountForTeacher(User $teacher): int
    {
        return $this->listForTeacher($teacher, openOnly: true)->count();
    }

    private function assertTeacher(User $teacher): void
    {
        if (! $teacher->hasRole(UserRole::Teacher) || ! $teacher->isActive()) {
            throw ValidationException::withMessages([
                'teacher' => 'غير مصرح.',
            ]);
        }
    }

    private function assertTeacherOwnsStudent(User $teacher, User $student): void
    {
        $this->assertTeacher($teacher);

        if (! $teacher->students()->where('users.id', $student->id)->exists()) {
            throw ValidationException::withMessages([
                'student' => 'الطالب غير مرتبط بك.',
            ]);
        }
    }

    private function assertTeacherOwnsFee(User $teacher, StudentFee $fee): void
    {
        $this->assertTeacher($teacher);

        if ((int) $fee->teacher_id !== (int) $teacher->id) {
            throw ValidationException::withMessages([
                'fee' => 'المصروف خارج نطاقك.',
            ]);
        }
    }
}
