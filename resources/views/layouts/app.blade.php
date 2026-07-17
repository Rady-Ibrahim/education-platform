<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'سنتر') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="surface-page" x-data="{ sidebarOpen: false }">
            <div class="min-h-screen lg:flex">
                <div
                    x-show="sidebarOpen"
                    x-cloak
                    @click="sidebarOpen = false"
                    class="fixed inset-0 z-40 bg-brand-950/40 backdrop-blur-sm lg:hidden"
                ></div>

                <aside
                    class="fixed inset-y-0 start-0 z-50 flex w-[17.5rem] translate-x-full flex-col border-e border-slate-200 bg-white transition-transform duration-200 lg:static lg:translate-x-0"
                    :class="sidebarOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'"
                >
                    <livewire:layout.navigation />
                </aside>

                <div class="flex min-w-0 flex-1 flex-col">
                    <header class="sticky top-0 z-30 flex items-center justify-between gap-3 border-b border-slate-200/80 bg-white/90 px-4 py-3 backdrop-blur lg:hidden">
                        <button
                            type="button"
                            @click="sidebarOpen = true"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-200 p-2 text-ink-muted hover:bg-slate-50"
                            aria-label="فتح القائمة"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        <a href="{{ route('dashboard') }}" class="text-lg font-bold text-brand-900" wire:navigate>{{ config('app.name', 'سنتر') }}</a>
                        <livewire:shared.notification-bell />
                    </header>

                    @if (isset($header))
                        <div class="border-b border-slate-200/80 bg-white">
                            <div class="page-shell !space-y-0 !py-5">
                                {{ $header }}
                            </div>
                        </div>
                    @endif

                    <main class="flex-1">
                        {{ $slot }}
                    </main>
                </div>
            </div>
        </div>
    </body>
</html>
