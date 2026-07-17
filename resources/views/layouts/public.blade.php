<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ isset($title) ? $title.' — ' : '' }}{{ config('app.name', 'سنتر') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-ink antialiased">
        <div class="surface-page flex min-h-screen flex-col">
            <header class="border-b border-brand-100/80 bg-white/80 backdrop-blur">
                <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
                    <a href="{{ url('/') }}" class="text-xl font-bold tracking-tight text-brand-900" wire:navigate>
                        {{ config('app.name', 'سنتر') }}
                    </a>
                    <nav class="flex flex-wrap items-center gap-3 text-sm font-medium">
                        <a href="{{ route('teachers.index') }}" class="text-ink-muted transition hover:text-brand-700" wire:navigate>المدرسون</a>
                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-lg bg-brand-700 px-3 py-1.5 text-white hover:bg-brand-800" wire:navigate>لوحتي</a>
                        @else
                            <a href="{{ route('login') }}" class="text-ink-muted transition hover:text-brand-700" wire:navigate>دخول</a>
                            <a href="{{ route('register') }}" class="rounded-lg bg-accent px-3 py-1.5 font-semibold text-ink hover:bg-accent-dark hover:text-white" wire:navigate>تسجيل</a>
                        @endauth
                    </nav>
                </div>
            </header>

            <main class="flex-1">
                {{ $slot }}
            </main>

            <footer class="mt-auto border-t border-brand-100 bg-white/70">
                <div class="mx-auto max-w-6xl px-4 py-8 text-sm text-ink-muted sm:px-6">
                    {{ config('app.name', 'سنتر') }} — منصة السنتر التعليمي
                </div>
            </footer>
        </div>
    </body>
</html>
