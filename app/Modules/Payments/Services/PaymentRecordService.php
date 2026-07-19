<?php

namespace App\Modules\Payments\Services;

use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Academic\Models\Branch;
use App\Modules\Identity\Services\ParentLinkService;
use App\Modules\Notifications\Services\NotificationService;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\Subscription;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentRecordService
{
    public function __construct(
        private readonly PaymentReviewService $review,
        private readonly NotificationService $notifications,
        private readonly ParentLinkService $parentLinks,
    ) {}

    /**
     * @param  array{amount: float|int, external_reference?: string|null, notes?: string|null}  $data
     */
    public function submitVodafoneProof(
        User $student,
        Subscription $subscription,
        array $data,
        ?UploadedFile $proof = null,
    ): Payment {
        if (! config('payments.student_vodafone_enabled')) {
            throw ValidationException::withMessages([
                'payment' => 'دفع فودافون كاش للمدرس يتم من حساب ولي الأمر فقط. ادفع كاش عند المدرس أو اطلب من ولي الأمر التحويل.',
            ]);
        }

        $this->assertStudentOwnsSubscription($student, $subscription);

        return $this->createVodafonePayment($student, $student, $subscription, $data, $proof);
    }

    /**
     * ولي الأمر يرسل إثبات فودافون كاش نيابة عن ابنه المرتبط.
     *
     * @param  array{amount: float|int, external_reference?: string|null, notes?: string|null}  $data
     */
    public function submitVodafoneProofForChild(
        User $parent,
        User $student,
        Subscription $subscription,
        array $data,
        ?UploadedFile $proof = null,
    ): Payment {
        if (! $this->parentLinks->parentCanViewStudent($parent, $student)) {
            throw ValidationException::withMessages([
                'student' => 'هذا الطالب غير مرتبط بحسابك.',
            ]);
        }

        $this->assertStudentOwnsSubscription($student, $subscription);

        return $this->createVodafonePayment($parent, $student, $subscription, $data, $proof);
    }

    /**
     * @param  array{amount: float|int, external_reference?: string|null, notes?: string|null}  $data
     */
    public function recordCash(User $recorder, User $student, Subscription $subscription, array $data): Payment
    {
        if ($subscription->student_id !== $student->id) {
            throw ValidationException::withMessages([
                'subscription' => 'الاشتراك لا يخص هذا الطالب.',
            ]);
        }

        $this->assertCanRecordForStudent($recorder, $student, $subscription);
        $subscription->loadMissing('plan', 'teacher');

        if ($subscription->status !== SubscriptionStatus::PendingPayment) {
            throw ValidationException::withMessages([
                'subscription' => 'الاشتراك ليس بانتظار الدفع.',
            ]);
        }

        return DB::transaction(function () use ($recorder, $student, $subscription, $data) {
            $payment = Payment::query()->create([
                'student_id' => $student->id,
                'teacher_id' => $subscription->teacher_id,
                'subscription_id' => $subscription->id,
                'branch_id' => $student->branch_id ?? Branch::defaultBranch()?->id,
                'channel' => PaymentChannel::Cash,
                'provider' => 'manual',
                'amount' => $data['amount'] ?? $subscription->plan->price,
                'external_reference' => $data['external_reference'] ?? null,
                'status' => PaymentStatus::PendingReview,
                'recorded_by' => $recorder->id,
                'notes' => $data['notes'] ?? null,
            ]);

            if ($recorder->hasAnyRole([UserRole::Teacher, UserRole::Admin])) {
                return $this->review->confirm($recorder, $payment);
            }

            return $payment;
        });
    }

    /**
     * تعليمات التحويل المعروضة للطالب/ولي الأمر.
     *
     * @return array{vodafone_cash_number: string|null, payment_instructions: string|null, teacher_name: string|null}
     */
    public function paymentInstructionsForSubscription(Subscription $subscription): array
    {
        $subscription->loadMissing(['teacher', 'branch', 'student.branch']);

        $teacher = $subscription->teacher;
        $branch = $subscription->branch
            ?? $subscription->student?->branch
            ?? Branch::defaultBranch();

        return [
            'vodafone_cash_number' => $teacher?->vodafone_cash_number ?: $branch?->vodafone_cash_number,
            'payment_instructions' => $teacher?->payment_instructions ?: $branch?->payment_instructions,
            'teacher_name' => $teacher?->name,
        ];
    }

    /**
     * @param  array{amount: float|int, external_reference?: string|null, notes?: string|null}  $data
     */
    private function createVodafonePayment(
        User $recorder,
        User $student,
        Subscription $subscription,
        array $data,
        ?UploadedFile $proof = null,
    ): Payment {
        if ($subscription->status !== SubscriptionStatus::PendingPayment) {
            throw ValidationException::withMessages([
                'subscription' => 'الاشتراك ليس بانتظار الدفع.',
            ]);
        }

        $hasPending = Payment::query()
            ->where('subscription_id', $subscription->id)
            ->where('status', PaymentStatus::PendingReview)
            ->exists();

        if ($hasPending) {
            throw ValidationException::withMessages([
                'payment' => 'يوجد إثبات دفع قيد المراجعة بالفعل لهذا الاشتراك.',
            ]);
        }

        $subscription->loadMissing('plan', 'teacher');

        if (! $proof) {
            throw ValidationException::withMessages([
                'proof' => 'صورة وصل فودافون كاش مطلوبة.',
            ]);
        }

        $proofPath = $proof->store('payment-proofs', 'public');

        $payment = DB::transaction(function () use ($recorder, $student, $subscription, $data, $proofPath) {
            return Payment::query()->create([
                'student_id' => $student->id,
                'teacher_id' => $subscription->teacher_id,
                'subscription_id' => $subscription->id,
                'branch_id' => $student->branch_id ?? Branch::defaultBranch()?->id,
                'channel' => PaymentChannel::VodafoneCash,
                'provider' => 'manual',
                'amount' => $data['amount'] ?? $subscription->plan->price,
                'external_reference' => $data['external_reference'] ?? null,
                'proof_path' => $proofPath,
                'status' => PaymentStatus::PendingReview,
                'recorded_by' => $recorder->id,
                'notes' => $data['notes'] ?? null,
            ]);
        });

        $this->notifications->notifyPaymentPendingReview($payment);

        return $payment;
    }

    private function assertStudentOwnsSubscription(User $student, Subscription $subscription): void
    {
        if ($subscription->student_id !== $student->id) {
            throw ValidationException::withMessages([
                'subscription' => 'غير مصرح.',
            ]);
        }
    }

    private function assertCanRecordForStudent(User $recorder, User $student, Subscription $subscription): void
    {
        if ($recorder->hasRole(UserRole::Admin)) {
            return;
        }

        if ($recorder->hasRole(UserRole::Teacher)) {
            if ($subscription->teacher_id !== $recorder->id) {
                throw ValidationException::withMessages([
                    'subscription' => 'الاشتراك خارج نطاقك.',
                ]);
            }

            if (! $recorder->students()->where('users.id', $student->id)->exists()) {
                throw ValidationException::withMessages([
                    'student' => 'الطالب غير مرتبط بك.',
                ]);
            }

            return;
        }

        if ($recorder->id === $student->id) {
            return;
        }

        throw ValidationException::withMessages([
            'payment' => 'غير مصرح بتسجيل الدفع.',
        ]);
    }
}
