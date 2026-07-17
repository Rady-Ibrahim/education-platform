<?php

namespace App\Enums;

enum QuestionType: string
{
    case Mcq = 'mcq';
    case TrueFalse = 'true_false';
    case Essay = 'essay';
    case FillBlank = 'fill_blank';

    public function label(): string
    {
        return match ($this) {
            self::Mcq => 'اختيار من متعدد',
            self::TrueFalse => 'صح / خطأ',
            self::Essay => 'مقالي',
            self::FillBlank => 'إكمال',
        };
    }

    public function isAutoGradable(): bool
    {
        return $this !== self::Essay;
    }
}
