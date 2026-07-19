<?php

namespace App\Enums;

enum ChargeStatus: string
{
    case Due = 'due';
    case Partial = 'partial';
    case Paid = 'paid';
    case Waived = 'waived';

    public function label(): string
    {
        return match ($this) {
            self::Due => 'مستحق',
            self::Partial => 'جزئي',
            self::Paid => 'مدفوع',
            self::Waived => 'معفى',
        };
    }

    public function isOpen(): bool
    {
        return $this === self::Due || $this === self::Partial;
    }
}
