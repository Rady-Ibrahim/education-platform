<?php

namespace App\Modules\Payments\Services;

use App\Modules\Payments\Models\Invoice;
use App\Modules\Payments\Models\Payment;
use Illuminate\Support\Str;

class InvoiceService
{
    public function issueForPayment(Payment $payment): Invoice
    {
        $payment->loadMissing('subscription');

        if ($payment->invoice) {
            return $payment->invoice;
        }

        return Invoice::query()->create([
            'payment_id' => $payment->id,
            'subscription_id' => $payment->subscription_id,
            'invoice_number' => $this->generateNumber(),
            'amount' => $payment->amount,
            'issued_at' => now(),
        ]);
    }

    private function generateNumber(): string
    {
        do {
            $number = 'INV-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Invoice::query()->where('invoice_number', $number)->exists());

        return $number;
    }
}
