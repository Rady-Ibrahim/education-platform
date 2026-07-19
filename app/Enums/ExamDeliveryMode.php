<?php

namespace App\Enums;

enum ExamDeliveryMode: string
{
    case Online = 'online';
    case Paper = 'paper';

    public function label(): string
    {
        return match ($this) {
            self::Online => 'إلكتروني',
            self::Paper => 'ورقي / يدوي',
        };
    }
}
