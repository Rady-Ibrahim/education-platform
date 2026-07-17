<?php

namespace App\Enums;

enum PlatformSubscriptionStatus: string
{
    case Trialing = 'trialing';
    case PendingPayment = 'pending_payment';
    case Active = 'active';
    case PastDue = 'past_due';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Trialing => 'فترة مجانية',
            self::PendingPayment => 'بانتظار دفع المنصة',
            self::Active => 'نشط',
            self::PastDue => 'متأخر',
            self::Suspended => 'موقوف',
        };
    }

    public function allowsTeacherAccess(): bool
    {
        return in_array($this, [self::Trialing, self::Active, self::PendingPayment], true);
    }
}
