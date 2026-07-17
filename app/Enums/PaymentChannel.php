<?php

namespace App\Enums;

enum PaymentChannel: string
{
    case VodafoneCash = 'vodafone_cash';
    case Cash = 'cash';

    public function label(): string
    {
        return match ($this) {
            self::VodafoneCash => 'فودافون كاش',
            self::Cash => 'كاش',
        };
    }
}
