<?php

namespace App\Enums;

enum LessonType: string
{
    case Text = 'text';
    case Video = 'video';
    case Mixed = 'mixed';
    case Live = 'live';

    public function label(): string
    {
        return match ($this) {
            self::Text  => 'نصي',
            self::Video => 'فيديو',
            self::Mixed => 'نص + فيديو',
            self::Live  => 'حصة مباشرة (Google Meet)',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Text  => '📄',
            self::Video => '🎬',
            self::Mixed => '🎬',
            self::Live  => '📡',
        };
    }
}
