<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tab-User Isolation Middleware
 *
 * Allows different browser tabs to be logged in as different users
 * simultaneously — without touching the session cookie or breaking CSRF.
 *
 * ── How it works ──
 *
 * 1. When a user logs in, the browser stores a signed, encrypted token in
 *    localStorage keyed by tabId: `tab_user_<tabId>`.
 *    The token contains: { userId, guardedAt (timestamp) }
 *
 * 2. Every subsequent request from that tab sends the token in the
 *    `X-Tab-User` header.
 *
 * 3. This middleware decrypts + verifies the token and calls
 *    Auth::onceUsingId() — which overrides whoever the session says is
 *    logged in, for this request only.
 *
 * 4. CSRF continues to work normally: the session and its CSRF token are
 *    untouched.  The meta[name=csrf-token] in the HTML is session-based and
 *    Livewire reads it correctly.
 *
 * ── Security ──
 *
 * • The token is encrypted + signed with APP_KEY (Crypt::encrypt).
 * • Tokens expire after TAB_TOKEN_TTL_HOURS hours.
 * • An attacker who steals the localStorage value can impersonate —
 *   identical risk to stealing a session cookie.
 * • onceUsingId() does NOT persist to session, so it never corrupts the
 *   underlying session user.
 */
class TabSessionIsolation
{
    public const HEADER = 'X-Tab-User';

    /** Token lifetime in hours */
    private const TTL_HOURS = 8;

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header(self::HEADER);

        if ($token) {
            $this->applyTabUser($request, $token);
        }

        return $next($request);
    }

    private function applyTabUser(Request $request, string $token): void
    {
        try {
            $payload = Crypt::decrypt($token);

            if (! is_array($payload) || empty($payload['user_id'])) {
                return;
            }

            // Check expiry
            $age = now()->diffInHours($payload['issued_at'] ?? 0, false);
            if ($age > self::TTL_HOURS || $age < 0) {
                return;
            }

            $userId = (int) $payload['user_id'];

            // Only override if it's a different user from the session
            if (Auth::id() !== $userId) {
                Auth::onceUsingId($userId);
            }

        } catch (DecryptException) {
            // Invalid / tampered token — ignore, fall back to session user
        }
    }

    /**
     * Create a signed token for the given user ID.
     * Called from the login response via JavaScript interop.
     */
    public static function createToken(int $userId): string
    {
        return Crypt::encrypt([
            'user_id'   => $userId,
            'issued_at' => now()->toIso8601String(),
        ]);
    }
}
