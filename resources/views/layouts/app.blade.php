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
        <div
            id="nav-progress"
            class="pointer-events-none fixed inset-x-0 top-0 z-[60] h-0.5 origin-right scale-x-0 bg-brand-600 transition-transform duration-300"
            aria-hidden="true"
        ></div>

        <div class="surface-page" x-data="{ sidebarOpen: false }">
            <div class="min-h-screen lg:flex">
                <div
                    x-show="sidebarOpen"
                    x-cloak
                    @click="sidebarOpen = false"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 z-40 bg-brand-950/40 backdrop-blur-sm lg:hidden"
                ></div>

                <aside
                    class="fixed inset-y-0 start-0 z-50 flex w-64 translate-x-full flex-col border-e border-slate-200/80 bg-white shadow-panel transition-transform duration-300 ease-spring lg:static lg:translate-x-0"
                    :class="sidebarOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'"
                >
                    @include('partials.sidebar')
                </aside>

                <div class="flex min-w-0 flex-1 flex-col">
                    <header class="sticky top-0 z-30 border-b border-slate-200/70 bg-white/95 backdrop-blur-md">
                        <div class="flex items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:px-8">
                            <div class="flex min-w-0 items-center gap-3">
                                <button
                                    type="button"
                                    @click="sidebarOpen = true"
                                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white p-2 text-ink-muted shadow-soft transition duration-200 hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700 lg:hidden"
                                    aria-label="فتح القائمة"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    </svg>
                                </button>

                                {{-- Page breadcrumb / title --}}
                                <div class="min-w-0 lg:hidden">
                                    <div class="flex items-center gap-2">
                                        <span class="brand-mark h-7 w-7 text-xs shadow-soft">س</span>
                                        <span class="truncate text-sm font-bold text-brand-900">{{ config('app.name', 'سنتر') }}</span>
                                    </div>
                                </div>
                                <div class="hidden min-w-0 lg:block">
                                    <div class="truncate text-sm font-semibold text-ink-muted">
                                        {{ match (auth()->user()?->primaryRole()) {
                                            \App\Enums\UserRole::Teacher => 'لوحة المدرس',
                                            \App\Enums\UserRole::Student => 'لوحة الطالب',
                                            \App\Enums\UserRole::Parent => 'لوحة ولي الأمر',
                                            \App\Enums\UserRole::Admin => 'لوحة الإدارة',
                                            default => config('app.name', 'سنتر'),
                                        } }}
                                    </div>
                                </div>
                            </div>

                            <livewire:layout.header-user-menu />
                        </div>
                    </header>

                    @isset($header)
                        <div class="border-b border-slate-200/80 bg-white">
                            <div class="page-shell !space-y-0 !py-5">
                                {{ $header }}
                            </div>
                        </div>
                    @endisset

                    <main class="flex-1">
                        {{ $slot }}
                    </main>
                </div>
            </div>
        </div>
    </body>
</html>
