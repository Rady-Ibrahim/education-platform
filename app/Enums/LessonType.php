<?php

namespace App\Enums;

enum LessonType: string
{
    case Text = 'text';
    case Video = 'video';
    case Mixed = 'mixed';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'نصي',
            self::Video => 'فيديو',
            self::Mixed => 'نص + فيديو',
        };
    }
}
