<?php

namespace App\Enums;

enum FeeCategory: string
{
    case Books = 'books';
    case Materials = 'materials';
    case Transport = 'transport';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Books => 'كتب',
            self::Materials => 'مستلزمات',
            self::Transport => 'مواصلات',
            self::Other => 'أخرى',
        };
    }
}
