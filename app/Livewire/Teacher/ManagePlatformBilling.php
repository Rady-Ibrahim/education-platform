<?php

namespace App\Livewire\Teacher;

use App\Modules\Payments\Models\PlatformPayment;
use App\Modules\Payments\Services\PlatformBillingService;
use App\Modules\Payments\Services\PlatformPaymentService;
use Livewire\Component;
use Livewire\WithFileUploads;

class ManagePlatformBilling extends Component
{
    use WithFileUploads;

    public string $externalReference = '';

    public $proof;

    public function submit(PlatformPaymentService $payments): void
    {
        $this->validate([
            'externalReference' => ['required', 'string', 'min:4', 'max:100'],
            'proof' => ['required', 'image', 'max:4096'],
        ]);

        $payments->submitVodafoneProof(auth()->user(), [
            'external_reference' => $this->externalReference,
        ], $this->proof);

        $this->reset(['externalReference', 'proof']);
        session()->flash('status', 'تم إرسال إثبات دفع المنصة وبانتظار مراجعة الإدارة.');
    }

    public function render(PlatformBillingService $billing)
    {
        $subscription = $billing->ensureSubscription(auth()->user());
        $settings = $billing->settings();
        $payments = PlatformPayment::query()
            ->where('teacher_id', auth()->id())
            ->latest()
            ->limit(10)
            ->get();

        return view('livewire.teacher.manage-platform-billing', [
            'subscription' => $subscription,
            'settings' => $settings,
            'payments' => $payments,
        ]);
    }
}
