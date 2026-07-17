<?php

namespace App\Enums;

enum ParentLinkStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Rejected = 'rejected';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'بانتظار الموافقة',
            self::Active => 'نشط',
            self::Rejected => 'مرفوض',
            self::Revoked => 'ملغى',
        };
    }
}
