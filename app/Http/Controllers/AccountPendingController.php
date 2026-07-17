<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AccountPendingController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->isActive()) {
            return redirect()->route('dashboard');
        }

        return view('account.pending', [
            'user' => $user,
        ]);
    }
}
