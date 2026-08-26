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
    {{-- Header --}}
    <div class="mb-7">
        <h1 class="text-2xl font-bold tracking-tight text-ink">أهلاً بك</h1>
        <p class="mt-1.5 text-sm text-ink-muted">ادخل بحسابك للمتابعة إلى لوحتك.</p>
    </div>

    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form wire:submit="login" class="space-y-4">
        <div>
            <x-input-label for="email" value="البريد الإلكتروني" />
            <x-text-input
                wire:model="form.email"
                id="email"
                class="mt-1.5 block w-full"
                type="email"
                name="email"
                required
                autofocus
                autocomplete="username"
                placeholder="name@example.com"
            />
            <x-input-error :messages="$errors->get('form.email')" class="mt-1.5" />
        </div>

        <div>
            <div class="flex items-center justify-between gap-3">
                <x-input-label for="password" value="كلمة المرور" />
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" wire:navigate class="text-xs font-semibold text-brand-700 transition hover:text-brand-900">
                        نسيت كلمة المرور؟
                    </a>
                @endif
            </div>
            <x-text-input
                wire:model="form.password"
                id="password"
                class="mt-1.5 block w-full"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="••••••••"
            />
            <x-input-error :messages="$errors->get('form.password')" class="mt-1.5" />
        </div>

        <label for="remember" class="flex cursor-pointer items-center gap-2.5">
            <input wire:model="form.remember" id="remember" type="checkbox" class="rounded border-slate-300 text-brand-700 shadow-sm focus:ring-brand-500" name="remember">
            <span class="text-sm text-ink-muted">تذكرني على هذا الجهاز</span>
        </label>

        <x-primary-button class="mt-2 w-full justify-center py-3 text-base">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
            دخول
        </x-primary-button>
    </form>

    <div class="mt-6 space-y-3 border-t border-slate-100 pt-5">
        <p class="text-center text-sm text-ink-muted">ليس لديك حساب؟</p>
        <div class="grid grid-cols-2 gap-2">
            <a href="{{ route('register', ['role' => 'student']) }}" wire:navigate class="btn-brand justify-center">
                سجّل كطالب
            </a>
            <a href="{{ route('register', ['role' => 'teacher']) }}" wire:navigate class="btn-ghost justify-center">
                سجّل كمدرس
            </a>
        </div>
        <a href="{{ route('register', ['role' => 'parent']) }}" wire:navigate class="block text-center text-xs text-ink-muted transition hover:text-brand-700">
            سجّل كولي أمر
        </a>
    </div>
</div>
