<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Modules\Payments\Services\PlatformBillingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeacherPlatformAccess
{
    public function __construct(
        private readonly PlatformBillingService $billing,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole(UserRole::Teacher)) {
            return $next($request);
        }

        if ($request->routeIs('teacher.platform*', 'profile', 'logout', 'account.pending')) {
            return $next($request);
        }

        if ($this->billing->teacherHasAccess($user)) {
            return $next($request);
        }

        return redirect()
            ->route('teacher.platform')
            ->with('status', 'انتهت الفترة المجانية. ادفع رسوم المنصة لفودافون كاش الإدارة للمتابعة.');
    }
}
