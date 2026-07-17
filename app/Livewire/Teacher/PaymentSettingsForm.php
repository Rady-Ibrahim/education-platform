<?php

namespace App\Livewire\Teacher;

use Livewire\Component;

class PaymentSettingsForm extends Component
{
    public string $vodafoneCashNumber = '';

    public string $paymentInstructions = '';

    public function mount(): void
    {
        $teacher = auth()->user();
        $this->vodafoneCashNumber = (string) ($teacher->vodafone_cash_number ?? '');
        $this->paymentInstructions = (string) ($teacher->payment_instructions ?? '');
    }

    public function save(): void
    {
        $this->validate([
            'vodafoneCashNumber' => ['nullable', 'string', 'max:32'],
            'paymentInstructions' => ['nullable', 'string', 'max:2000'],
        ]);

        auth()->user()->update([
            'vodafone_cash_number' => $this->vodafoneCashNumber !== '' ? $this->vodafoneCashNumber : null,
            'payment_instructions' => $this->paymentInstructions !== '' ? $this->paymentInstructions : null,
        ]);

        session()->flash('status', 'تم حفظ بيانات التحويل لطلابك وأولياء أمورهم.');
    }

    public function render()
    {
        return view('livewire.teacher.payment-settings-form');
    }
}
