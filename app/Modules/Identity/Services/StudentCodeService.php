<?php

namespace App\Modules\Identity\Services;

use App\Models\User;
use Illuminate\Support\Str;

class StudentCodeService
{
    public function generate(): string
    {
        do {
            $code = 'STU-'.now()->format('y').'-'.Str::upper(Str::random(5));
        } while (User::query()->where('student_code', $code)->exists());

        return $code;
    }

    public function findActiveStudentByCode(string $code): ?User
    {
        $student = User::query()
            ->where('student_code', strtoupper(trim($code)))
            ->first();

        if (! $student || ! $student->hasRole(\App\Enums\UserRole::Student) || ! $student->isActive()) {
            return null;
        }

        return $student;
    }
}
