<?php

namespace App\Livewire\Admin;

use App\Modules\Payments\Models\PlatformPayment;
use App\Modules\Payments\Services\PlatformBillingService;
use App\Modules\Payments\Services\PlatformPaymentService;
use Livewire\Component;
use Livewire\WithPagination;

class PlatformBillingAdmin extends Component
{
    use WithPagination;

    public string $vodafoneCashNumber = '';

    public string $paymentInstructions = '';

    public int $trialDays = 90;

    public string $monthlyFee = '200';

    public int $periodDays = 30;

    public string $rejectionReason = '';

    public ?int $rejectingPaymentId = null;

    public function mount(PlatformBillingService $billing): void
    {
        $settings = $billing->settings();
        $this->vodafoneCashNumber = (string) ($settings->vodafone_cash_number ?? '');
        $this->paymentInstructions = (string) ($settings->payment_instructions ?? '');
        $this->trialDays = (int) $settings->trial_days;
        $this->monthlyFee = (string) $settings->monthly_fee;
        $this->periodDays = (int) $settings->period_days;
    }

    public function saveSettings(PlatformBillingService $billing): void
    {
        $this->validate([
            'vodafoneCashNumber' => ['nullable', 'string', 'max:32'],
            'paymentInstructions' => ['nullable', 'string', 'max:2000'],
            'trialDays' => ['required', 'integer', 'min:0', 'max:365'],
            'monthlyFee' => ['required', 'numeric', 'min:0'],
            'periodDays' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $billing->updateSettings(auth()->user(), [
            'vodafone_cash_number' => $this->vodafoneCashNumber !== '' ? $this->vodafoneCashNumber : null,
            'payment_instructions' => $this->paymentInstructions !== '' ? $this->paymentInstructions : null,
            'trial_days' => $this->trialDays,
            'monthly_fee' => (float) $this->monthlyFee,
            'period_days' => $this->periodDays,
        ]);

        session()->flash('status', 'تم حفظ إعدادات اشتراك المنصة.');
    }

    public function confirm(int $paymentId, PlatformPaymentService $payments): void
    {
        $payment = PlatformPayment::query()->findOrFail($paymentId);
        $payments->confirm(auth()->user(), $payment);
        session()->flash('status', 'تم تأكيد دفعة المنصة وتفعيل فترة المدرس.');
    }

    public function startReject(int $paymentId): void
    {
        $this->rejectingPaymentId = $paymentId;
        $this->rejectionReason = '';
    }

    public function confirmReject(PlatformPaymentService $payments): void
    {
        $this->validate([
            'rejectionReason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $payment = PlatformPayment::query()->findOrFail($this->rejectingPaymentId);
        $payments->reject(auth()->user(), $payment, $this->rejectionReason);
        $this->rejectingPaymentId = null;
        $this->rejectionReason = '';
        session()->flash('status', 'تم رفض دفعة المنصة.');
    }

    public function render()
    {
        return view('livewire.admin.platform-billing-admin', [
            'pendingPayments' => PlatformPayment::query()
                ->with('teacher')
                ->where('status', \App\Enums\PaymentStatus::PendingReview)
                ->latest()
                ->paginate(10),
        ]);
    }
}
