<?php

namespace App\Enums;

enum GroupMembershipStatus: string
{
    case Active = 'active';
    case Stopped = 'stopped';
    case Frozen = 'frozen';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'مستمر',
            self::Stopped => 'متوقف',
            self::Frozen => 'مجمد مؤقتًا',
        };
    }

    public function isOperational(): bool
    {
        return $this === self::Active;
    }
}
