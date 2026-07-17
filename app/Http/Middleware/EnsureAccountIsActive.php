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
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->status === UserStatus::Suspended) {
            auth()->logout();

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'تم إيقاف حسابك. تواصل مع الإدارة.']);
        }

        if (! $user->isActive()) {
            if ($request->routeIs('account.pending', 'profile', 'logout', 'verification.*', 'password.*')) {
                return $next($request);
            }

            return redirect()->route('account.pending');
        }

        return $next($request);
    }
}
