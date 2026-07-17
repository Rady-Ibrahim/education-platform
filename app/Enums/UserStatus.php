<?php

namespace App\Enums;

enum UserStatus: string
{
    case PendingAdmin = 'pending_admin';
    case Active = 'active';
    case Rejected = 'rejected';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::PendingAdmin => 'بانتظار موافقة الإدارة',
            self::Active => 'نشط',
            self::Rejected => 'مرفوض',
            self::Suspended => 'موقوف',
        };
    }

    public function canAccessPlatform(): bool
    {
        return $this === self::Active;
    }
}
