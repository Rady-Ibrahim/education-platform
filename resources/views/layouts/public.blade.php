<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ isset($title) ? $title.' — ' : '' }}{{ config('app.name', 'سنتر') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-ink antialiased">
        <div class="surface-page flex min-h-screen flex-col">

            {{-- ── Navbar ── --}}
            <header class="sticky top-0 z-30 border-b border-brand-100/70 bg-white/90 backdrop-blur-md">
                <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3.5 sm:px-6">
                    <a href="{{ url('/') }}" class="group flex items-center gap-2.5" wire:navigate>
                        <span class="brand-mark h-8 w-8 text-[0.875rem] shadow-soft transition duration-200 group-hover:bg-brand-800">س</span>
                        <span class="text-[1.1rem] font-bold tracking-tight text-brand-900">{{ config('app.name', 'سنتر') }}</span>
                    </a>

                    <nav class="flex flex-wrap items-center gap-2 text-sm font-medium">
                        <a
                            href="{{ route('teachers.index') }}"
                            wire:navigate
                            @class([
                                'rounded-xl px-3.5 py-2 transition duration-200',
                                'bg-brand-50 font-semibold text-brand-900' => request()->routeIs('teachers.index'),
                                'text-ink-muted hover:bg-slate-100 hover:text-ink' => !request()->routeIs('teachers.index'),
                            ])
                        >
                            المدرسون
                        </a>
                        @auth
                            <a href="{{ route('dashboard') }}" wire:navigate class="rounded-xl bg-brand-700 px-4 py-2 font-semibold text-white shadow-soft transition duration-200 hover:bg-brand-800 active:scale-[0.97]">
                                لوحتي
                            </a>
                        @else
                            <a href="{{ route('login') }}" wire:navigate class="rounded-xl px-3.5 py-2 text-ink-muted transition duration-200 hover:bg-slate-100 hover:text-ink">
                                دخول
                            </a>
                            <a href="{{ route('register') }}" wire:navigate class="rounded-xl bg-accent px-4 py-2 font-bold text-ink shadow-soft transition duration-200 hover:bg-accent-dark hover:text-white active:scale-[0.97]">
                                تسجيل
                            </a>
                        @endauth
                    </nav>
                </div>
            </header>

            <main class="flex-1">
                {{ $slot }}
            </main>

            {{-- ── Footer ── --}}
            <footer class="mt-auto border-t border-brand-100/70 bg-white/70">
                <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
                    <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                        <div class="flex items-center gap-2.5">
                            <span class="brand-mark h-7 w-7 text-xs">س</span>
                            <span class="text-sm font-bold text-brand-900">{{ config('app.name', 'سنتر') }}</span>
                        </div>
                        <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-ink-muted">
                            <a href="{{ route('teachers.index') }}" class="transition hover:text-brand-700" wire:navigate>المدرسون</a>
                            @guest
                                <a href="{{ route('login') }}" class="transition hover:text-brand-700" wire:navigate>دخول</a>
                                <a href="{{ route('register') }}" class="transition hover:text-brand-700" wire:navigate>تسجيل</a>
                            @endguest
                        </div>
                        <p class="text-xs text-ink-muted">© {{ date('Y') }} {{ config('app.name', 'سنتر') }}</p>
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
