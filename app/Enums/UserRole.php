<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Teacher = 'teacher';
    case Student = 'student';
    case Parent = 'parent';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'أدمن',
            self::Teacher => 'مدرس',
            self::Student => 'طالب',
            self::Parent => 'ولي أمر',
        };
    }

    public function homeRoute(): string
    {
        return match ($this) {
            self::Admin => 'admin.dashboard',
            self::Teacher => 'teacher.dashboard',
            self::Student => 'student.dashboard',
            self::Parent => 'parent.dashboard',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
