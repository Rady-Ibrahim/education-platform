<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-ink">تسجيل الدخول</h1>
        <p class="mt-1 text-sm text-ink-muted">ادخل بحسابك للمتابعة إلى لوحتك.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login" class="space-y-4">
        <div>
            <x-input-label for="email" value="البريد الإلكتروني" />
            <x-text-input wire:model="form.email" id="email" class="mt-1 block w-full" type="email" name="email" required autofocus autocomplete="username" placeholder="name@example.com" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <div>
            <div class="flex items-center justify-between gap-3">
                <x-input-label for="password" value="كلمة المرور" />
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-xs font-medium text-brand-700 hover:text-brand-900" wire:navigate>
                        نسيت كلمة المرور؟
                    </a>
                @endif
            </div>
            <x-text-input
                wire:model="form.password"
                id="password"
                class="mt-1 block w-full"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="••••••••"
            />
            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <label for="remember" class="inline-flex items-center gap-2">
            <input wire:model="form.remember" id="remember" type="checkbox" class="rounded border-slate-300 text-brand-700 shadow-sm focus:ring-brand-500" name="remember">
            <span class="text-sm text-ink-muted">تذكرني على هذا الجهاز</span>
        </label>

        <x-primary-button class="w-full justify-center py-3">دخول</x-primary-button>
    </form>

    <div class="mt-6 space-y-3 border-t border-slate-100 pt-5 text-center text-sm">
        <p class="text-ink-muted">ليس لديك حساب؟</p>
        <div class="flex flex-col gap-2 sm:flex-row">
            <a href="{{ route('register', ['role' => 'student']) }}" class="btn-brand flex-1" wire:navigate>سجّل كطالب</a>
            <a href="{{ route('register', ['role' => 'teacher']) }}" class="inline-flex flex-1 items-center justify-center rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-ink transition hover:bg-slate-50" wire:navigate>سجّل كمدرس</a>
        </div>
    </div>
</div>
