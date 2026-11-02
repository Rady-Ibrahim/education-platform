<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // NOTE: TabSessionIsolation runs before this middleware in the web group,
        // so auth()->user() here already reflects the per-tab override (if any).
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Hard-block suspended users — log them out entirely
        if ($user->status === UserStatus::Suspended) {
            auth()->logout();

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'تم إيقاف حسابك. تواصل مع الإدارة.']);
        }

        // Non-active users (PendingAdmin / PendingTeacher / Rejected) get the pending page
        if (! $user->isActive()) {
            $allowedRoutes = [
                'account.pending',
                'profile',
                'logout',
                'tab.token',          // allow token fetch even for pending users
                'verification.*',
                'password.*',
            ];

            if ($request->routeIs(...$allowedRoutes)) {
                return $next($request);
            }

            return redirect()->route('account.pending');
        }

        return $next($request);
    }
}
