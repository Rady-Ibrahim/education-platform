<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

class AuditLogger
{
    public static function payment(string $action, array $context = []): void
    {
        Log::info('payment.'.$action, $context);
    }

    public static function exam(string $action, array $context = []): void
    {
        Log::info('exam.'.$action, $context);
    }

    public static function authFailed(string $email, array $context = []): void
    {
        Log::warning('auth.login_failed', array_merge(['email' => $email], $context));
    }
}
