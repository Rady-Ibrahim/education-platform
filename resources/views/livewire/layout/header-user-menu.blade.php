<?php

use App\Enums\UserRole;
use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }

    public function switchAccount(Logout $logout): void
    {
        $logout();

        $this->redirect(route('login', absolute: false), navigate: true);
    }

    public function roleLabel(): string
    {
        $user = auth()->user();

        return match ($user?->primaryRole()) {
            UserRole::Teacher => $user->headline ?: 'مدرس',
            UserRole::Student => 'طالب',
            UserRole::Parent => 'ولي أمر',
            UserRole::Admin => 'مدير النظام',
            default => 'مستخدم',
        };
    }
}; ?>

<div class="flex items-center gap-1.5 sm:gap-2">
    <livewire:shared.notification-bell lazy />

    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
        <button
            type="button"
            @click="open = ! open"
            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white py-1.5 pe-2.5 ps-1.5 text-sm shadow-soft transition duration-200 hover:border-brand-200 hover:bg-brand-50/70"
            aria-label="قائمة الحساب"
            :aria-expanded="open"
        >
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-700 text-sm font-bold text-white shadow-soft">
                {{ mb_substr(auth()->user()->name, 0, 1) }}
            </span>
            <span class="hidden min-w-0 text-start md:block">
                <span
                    class="block max-w-[10rem] truncate text-sm font-bold text-ink"
                    x-data="{{ json_encode(['name' => auth()->user()->name]) }}"
                    x-text="name"
                    x-on:profile-updated.window="name = $event.detail.name"
                ></span>
                <span class="block max-w-[10rem] truncate text-[11px] text-ink-muted">{{ $this->roleLabel() }}</span>
            </span>
            <svg
                class="hidden h-3.5 w-3.5 text-ink-muted transition duration-200 sm:block"
                :class="open ? 'rotate-180' : ''"
                fill="none" stroke="currentColor" viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div
            x-show="open"
            x-cloak
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
            class="absolute end-0 z-50 mt-2 w-60 overflow-hidden rounded-2xl border border-slate-200/80 bg-white py-1.5 shadow-panel"
        >
            {{-- Mobile header --}}
            <div class="border-b border-slate-100 px-4 py-3 sm:hidden">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-700 text-sm font-bold text-white">
                        {{ mb_substr(auth()->user()->name, 0, 1) }}
                    </span>
                    <div class="min-w-0">
                        <div class="truncate text-sm font-bold text-ink">{{ auth()->user()->name }}</div>
                        <div class="truncate text-[11px] text-ink-muted">{{ $this->roleLabel() }}</div>
                    </div>
                </div>
            </div>

            <a
                href="{{ route('profile') }}"
                wire:navigate
                @click="open = false"
                class="flex items-center gap-2.5 px-4 py-2.5 text-sm font-medium text-ink transition duration-150 hover:bg-brand-50 hover:text-brand-900"
            >
                <svg class="h-4 w-4 text-ink-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                الملف الشخصي
            </a>

            <button
                type="button"
                wire:click="switchAccount"
                class="flex w-full items-center gap-2.5 px-4 py-2.5 text-sm font-medium text-ink transition duration-150 hover:bg-brand-50 hover:text-brand-900"
            >
                <svg class="h-4 w-4 text-ink-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
                تبديل الحساب
            </button>

            <div class="my-1 border-t border-slate-100"></div>

            <button
                type="button"
                wire:click="logout"
                class="flex w-full items-center gap-2.5 px-4 py-2.5 text-sm font-medium text-rose-600 transition duration-150 hover:bg-rose-50"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                تسجيل الخروج
            </button>
        </div>
    </div>
</div>
