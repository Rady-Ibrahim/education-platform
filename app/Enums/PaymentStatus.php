<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PendingReview = 'pending_review';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PendingReview => 'بانتظار المراجعة',
            self::Confirmed => 'مؤكد',
            self::Rejected => 'مرفوض',
        };
    }
}
