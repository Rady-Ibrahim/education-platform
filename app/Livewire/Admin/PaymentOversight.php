<?php

namespace App\Livewire\Admin;

use App\Enums\PaymentStatus;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Services\PaymentReviewService;
use Livewire\Component;
use Livewire\WithPagination;

class PaymentOversight extends Component
{
    use WithPagination;

    public ?int $rejectingPaymentId = null;

    public string $rejectionReason = '';

    public function confirm(int $paymentId, PaymentReviewService $review): void
    {
        $payment = Payment::query()->findOrFail($paymentId);
        $review->confirm(auth()->user(), $payment);
        session()->flash('status', 'تم تأكيد الدفع.');
    }

    public function startReject(int $paymentId): void
    {
        $this->rejectingPaymentId = $paymentId;
        $this->rejectionReason = '';
    }

    public function confirmReject(PaymentReviewService $review): void
    {
        $this->validate([
            'rejectionReason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $payment = Payment::query()->findOrFail($this->rejectingPaymentId);
        $review->reject(auth()->user(), $payment, $this->rejectionReason);

        $this->rejectingPaymentId = null;
        $this->rejectionReason = '';
        session()->flash('status', 'تم رفض الدفع.');
    }

    public function render()
    {
        $payments = Payment::query()
            ->with(['student', 'teacher', 'subscription.plan'])
            ->where('status', PaymentStatus::PendingReview)
            ->latest()
            ->paginate(15);

        return view('livewire.admin.payment-oversight', [
            'payments' => $payments,
        ]);
    }
}
