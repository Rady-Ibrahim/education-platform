<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case PendingPayment = 'pending_payment';
    case Active = 'active';
    case Expired = 'expired';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'بانتظار الدفع',
            self::Active => 'نشط',
            self::Expired => 'منتهي',
            self::Suspended => 'موقوف',
        };
    }

    public function grantsAccess(): bool
    {
        return $this === self::Active;
    }
}
