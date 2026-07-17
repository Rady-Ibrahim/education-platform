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
                <img src="{{ asset('images/home/hero.jpg') }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-40">
                <div class="absolute inset-0 bg-gradient-to-t from-brand-950 via-brand-950/80 to-brand-900/60"></div>
                <div class="relative flex h-full flex-col justify-between p-10 text-white">
                    <a href="{{ url('/') }}" class="text-2xl font-bold tracking-tight" wire:navigate>{{ config('app.name', 'سنتر') }}</a>
                    <div class="max-w-md space-y-4">
                        <p class="text-sm font-semibold text-accent-soft">منصة السنتر التعليمي</p>
                        <h1 class="text-4xl font-bold leading-tight">تابع دروسك ومدفوعاتك بسهولة</h1>
                        <p class="text-brand-100/85">دخول آمن للطلاب والمدرسين وأولياء الأمور — كل شيء من مكان واحد.</p>
                    </div>
                    <p class="text-sm text-brand-100/60">© {{ date('Y') }} {{ config('app.name', 'سنتر') }}</p>
                </div>
            </div>

            <div class="flex min-h-screen flex-col justify-center bg-[#F3F6F8] px-4 py-10 sm:px-8">
                <div class="mx-auto w-full max-w-md">
                    <div class="mb-8 text-center lg:hidden">
                        <a href="/" wire:navigate class="inline-flex flex-col items-center gap-2">
                            <span class="brand-mark h-12 w-12 text-lg">س</span>
                            <span class="text-xl font-bold text-brand-900">{{ config('app.name', 'سنتر') }}</span>
                        </a>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-panel sm:p-8">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
