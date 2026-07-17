<?php

namespace App\Enums;

enum ExamAttemptStatus: string
{
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case Graded = 'graded';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'جارٍ',
            self::Submitted => 'مُسلَّم',
            self::Graded => 'مُصحَّح',
        };
    }
}
