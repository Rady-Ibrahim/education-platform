<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tab User Isolation Middleware
 *
 * Allows different browser tabs to be logged in as different users
 * simultaneously without touching the session cookie or breaking CSRF.
 *
 * How it works:
 * ─ Every AJAX/Livewire request carries an `X-Tab-User` header containing
 *   an encrypted {user_id, issued_at} payload (8h TTL).
 * ─ This middleware decrypts it and calls Auth::onceUsingId() to override
 *   the session user for that single request only.
 * ─ CSRF is untouched — Livewire reads it from meta[name=csrf-token].
 *
 * Security:
 * ─ Token is encrypted+signed with APP_KEY via Crypt::encrypt.
 * ─ Tokens expire after TTL_HOURS (default 8h).
 * ─ onceUsingId() never persists to session, so the underlying session
 *   user is always the last user who did a full Auth::login().
 */
class TabSessionIsolation
{
    public const HEADER = 'X-Tab-User';

    private const TTL_HOURS = 8;

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header(self::HEADER);

        if ($token) {
            $this->applyTabUser($token);
        }

        return $next($request);
    }

    private function applyTabUser(string $token): void
    {
        try {
            $payload = Crypt::decrypt($token);

            if (! is_array($payload) || empty($payload['user_id'])) {
                return;
            }

            // Check token age
            $issuedAt = $payload['issued_at'] ?? null;
            if ($issuedAt) {
                $age = now()->diffInHours($issuedAt, false);
                if ($age > self::TTL_HOURS || $age < 0) {
                    return;
                }
            }

            $userId = (int) $payload['user_id'];

            // Only override if this tab is running as a different user
            if (Auth::id() !== $userId) {
                Auth::onceUsingId($userId);
            }

        } catch (DecryptException) {
            // Tampered / invalid token — silently ignore, fall back to session user
        }
    }

    /**
     * Create a signed token for the given user ID.
     * Called by the /tab/token route after login.
     */
    public static function createToken(int $userId): string
    {
        return Crypt::encrypt([
            'user_id'   => $userId,
            'issued_at' => now()->toIso8601String(),
        ]);
    }
}
