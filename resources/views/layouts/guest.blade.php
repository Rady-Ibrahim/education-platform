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
    <body class="font-sans text-ink antialiased">
        <div class="min-h-screen lg:grid lg:grid-cols-2">
            <div class="relative hidden overflow-hidden bg-brand-950 lg:block">
                <img src="{{ asset('images/home/hero.jpg') }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-30">
                <div class="absolute inset-0 bg-gradient-to-br from-brand-950/90 via-brand-800/60 to-brand-950/80"></div>
                {{-- Subtle dot pattern --}}
                <div class="absolute inset-0 opacity-[0.06]" style="background-image: radial-gradient(circle, rgba(255,255,255,0.8) 1px, transparent 1px); background-size: 32px 32px;"></div>
                {{-- Radial glow --}}
                <div class="absolute inset-0" style="background: radial-gradient(ellipse at 20% 50%, rgba(74,160,179,0.25), transparent 55%), radial-gradient(ellipse at 80% 80%, rgba(226,160,8,0.12), transparent 45%);"></div>
                <div class="relative flex h-full flex-col justify-between p-10 text-white">
                    <a href="{{ url('/') }}" class="flex items-center gap-3" wire:navigate>
                        <span class="brand-mark h-10 w-10 text-base shadow-soft">س</span>
                        <span class="text-2xl font-bold tracking-tight">{{ config('app.name', 'سنتر') }}</span>
                    </a>
                    <div class="max-w-sm space-y-4">
                        <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-accent-soft">منصة السنتر التعليمي</p>
                        <h1 class="text-4xl font-extrabold leading-tight tracking-tight">تابع دروسك ومدفوعاتك<br>بسهولة</h1>
                        <p class="text-base leading-relaxed text-brand-100/75">دخول آمن للطلاب والمدرسين وأولياء الأمور — كل شيء من مكان واحد.</p>
                        <div class="flex flex-wrap gap-2 pt-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-xs font-semibold text-white/85 ring-1 ring-white/15">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                                دروس محمية
                            </span>
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-xs font-semibold text-white/85 ring-1 ring-white/15">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                                امتحانات إلكترونية
                            </span>
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-xs font-semibold text-white/85 ring-1 ring-white/15">
                                <span class="h-1.5 w-1.5 rounded-full bg-accent"></span>
                                فودافون كاش
                            </span>
                        </div>
                    </div>
                    <p class="text-sm text-brand-100/40">© {{ date('Y') }} {{ config('app.name', 'سنتر') }}</p>
                </div>
            </div>

            <div class="flex min-h-screen flex-col justify-center bg-[#F2F6F8] px-4 py-10 sm:px-8">
                <div class="mx-auto w-full max-w-md">
                    <div class="mb-8 text-center lg:hidden">
                        <a href="/" wire:navigate class="inline-flex flex-col items-center gap-2">
                            <span class="brand-mark h-12 w-12 text-lg shadow-soft">س</span>
                            <span class="text-xl font-bold text-brand-900">{{ config('app.name', 'سنتر') }}</span>
                        </a>
                    </div>

                    <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-panel">
                        <div class="h-1.5 w-full bg-gradient-to-l from-brand-400 via-brand-600 to-brand-800"></div>
                        <div class="p-6 sm:p-8">
                            {{ $slot }}
                        </div>
                    </div>

                    <p class="mt-6 text-center text-xs text-ink-muted/60">
                        {{ config('app.name', 'سنتر') }} — منصة السنتر التعليمي
                    </p>
                </div>
            </div>
        </div>
    </body>
</html>
