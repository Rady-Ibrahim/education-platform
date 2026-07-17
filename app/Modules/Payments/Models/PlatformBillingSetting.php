<?php

namespace App\Modules\Payments\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformBillingSetting extends Model
{
    protected $fillable = [
        'vodafone_cash_number',
        'payment_instructions',
        'trial_days',
        'monthly_fee',
        'period_days',
    ];

    protected function casts(): array
    {
        return [
            'trial_days' => 'integer',
            'monthly_fee' => 'decimal:2',
            'period_days' => 'integer',
        ];
    }

    public static function current(): self
    {
        $existing = static::query()->first();
        if ($existing) {
            return $existing;
        }

        return static::query()->create([
            'trial_days' => (int) config('payments.platform.trial_days', 90),
            'monthly_fee' => (float) config('payments.platform.default_monthly_fee', 200),
            'period_days' => (int) config('payments.platform.period_days', 30),
            'vodafone_cash_number' => null,
            'payment_instructions' => 'حوّل رسوم المنصة لفودافون كاش الإدارة ثم ارفع رقم العملية والإثبات.',
        ]);
    }
}
