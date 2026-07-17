<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ isset($title) ? $title.' — ' : '' }}{{ config('app.name', 'سنتر') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=cairo:400,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>body { font-family: Cairo, ui-sans-serif, system-ui, sans-serif; }</style>
    </head>
    <body class="bg-slate-50 text-slate-900 antialiased">
        <header class="border-b border-slate-200/80 bg-white/90 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
                <a href="{{ url('/') }}" class="text-xl font-bold tracking-tight text-teal-800" wire:navigate>
                    {{ config('app.name', 'سنتر') }}
                </a>
                <nav class="flex flex-wrap items-center gap-3 text-sm font-medium">
                    <a href="{{ route('teachers.index') }}" class="text-slate-600 hover:text-teal-700" wire:navigate>المدرسون</a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-slate-600 hover:text-teal-700" wire:navigate>لوحتي</a>
                    @else
                        <a href="{{ route('login') }}" class="text-slate-600 hover:text-teal-700" wire:navigate>دخول</a>
                        <a href="{{ route('register') }}" class="rounded-md bg-teal-700 px-3 py-1.5 text-white hover:bg-teal-800" wire:navigate>تسجيل</a>
                    @endauth
                </nav>
            </div>
        </header>

        <main>
            {{ $slot }}
        </main>

        <footer class="mt-16 border-t border-slate-200 bg-white">
            <div class="mx-auto max-w-6xl px-4 py-8 text-sm text-slate-500 sm:px-6">
                {{ config('app.name', 'سنتر') }} — منصة السنتر التعليمي متعدد المدرسين
            </div>
        </footer>
    </body>
</html>
