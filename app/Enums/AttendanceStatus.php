<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Absent = 'absent';
    case Late = 'late';
    case Excused = 'excused';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'حاضر',
            self::Absent => 'غائب',
            self::Late => 'متأخر',
            self::Excused => 'بعذر',
        };
    }

    public function isAbsentLike(): bool
    {
        return $this === self::Absent || $this === self::Excused;
    }
}
