<?php

namespace App\Enums;

enum ParentRelationship: string
{
    case Father = 'father';
    case Mother = 'mother';
    case Guardian = 'guardian';

    public function label(): string
    {
        return match ($this) {
            self::Father => 'أب',
            self::Mother => 'أم',
            self::Guardian => 'وصي',
        };
    }
}
