<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class DashboardController extends Controller
{
    public function __invoke(): RedirectResponse|View
    {
        $user = auth()->user();

        if (! $user->isActive()) {
            return redirect()->route('account.pending');
        }

        foreach (UserRole::cases() as $role) {
            if ($user->hasRole($role->value)) {
                return redirect()->route($role->homeRoute());
            }
        }

        return view('dashboard');
    }
}
