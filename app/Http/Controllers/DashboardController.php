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

        foreach (UserRole::cases() as $role) {
            if ($user->hasRole($role->value)) {
                return redirect()->route($role->homeRoute());
            }
        }

        return view('dashboard');
    }
}
