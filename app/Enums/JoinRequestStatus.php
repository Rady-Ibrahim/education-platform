<?php

namespace App\Enums;

enum JoinRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'بانتظار موافقة المدرس',
            self::Approved => 'مقبول',
            self::Rejected => 'مرفوض',
        };
    }
}
