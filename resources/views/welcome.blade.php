<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'سنتر') }} — منصة السنتر التعليمي</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=cairo:400,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>body { font-family: Cairo, ui-sans-serif, system-ui, sans-serif; }</style>
    </head>
    <body class="antialiased text-slate-900">
        <div class="relative min-h-screen overflow-hidden bg-gradient-to-bl from-teal-950 via-slate-900 to-teal-900 text-white">
            <div class="pointer-events-none absolute inset-0 opacity-40" style="background-image: radial-gradient(circle at 20% 20%, rgba(45,212,191,.35), transparent 40%), radial-gradient(circle at 80% 0%, rgba(14,165,233,.25), transparent 35%);"></div>

            <div class="relative mx-auto flex min-h-screen max-w-6xl flex-col px-4 py-8 sm:px-6">
                <header class="flex items-center justify-between gap-4">
                    <div class="text-2xl font-bold tracking-tight">{{ config('app.name', 'سنتر') }}</div>
                    <nav class="flex flex-wrap items-center gap-3 text-sm font-medium">
                        <a href="{{ route('teachers.index') }}" class="text-teal-100 hover:text-white">المدرسون</a>
                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-md bg-white/10 px-3 py-1.5 hover:bg-white/20">لوحتي</a>
                        @else
                            <a href="{{ route('login') }}" class="text-teal-100 hover:text-white">دخول</a>
                            <a href="{{ route('register') }}" class="rounded-md bg-teal-400 px-3 py-1.5 font-semibold text-teal-950 hover:bg-teal-300">تسجيل</a>
                        @endauth
                    </nav>
                </header>

                <main class="flex flex-1 flex-col justify-center py-16">
                    <div class="max-w-2xl">
                        <h1 class="text-4xl font-bold leading-tight sm:text-5xl">{{ config('app.name', 'سنتر') }}</h1>
                        <p class="mt-4 text-lg text-teal-100/90">
                            منصة السنتر متعدد المدرسين — شوف المدرسين، انضم لمجموعتهم، وادفع كاش في السنتر أو فودافون كاش (غالبًا نهاية الشهر).
                        </p>
                        <div class="mt-8 flex flex-wrap gap-3">
                            <a href="{{ route('teachers.index') }}" class="rounded-md bg-teal-400 px-5 py-3 text-sm font-semibold text-teal-950 hover:bg-teal-300">
                                تصفّح المدرسين بدون تسجيل
                            </a>
                            <a href="{{ route('register', ['role' => 'teacher']) }}" class="rounded-md border border-white/30 px-5 py-3 text-sm font-semibold text-white hover:bg-white/10">
                                سجّل كمدرس
                            </a>
                        </div>
                    </div>

                    <div class="mt-16 grid gap-6 sm:grid-cols-3">
                        <div>
                            <h2 class="font-semibold text-teal-200">للمدرس</h2>
                            <p class="mt-2 text-sm text-teal-100/80">يضيف طلابه من السنتر، يسجّل الكاش، يراجع فودافون كاش، ويدير الدروس والامتحانات.</p>
                        </div>
                        <div>
                            <h2 class="font-semibold text-teal-200">للطالب وولي الأمر</h2>
                            <p class="mt-2 text-sm text-teal-100/80">انضمام للمدرس، اشتراك، دفع فودافون، ومتابعة التقدم — بدون انتظار موافقة الإدارة.</p>
                        </div>
                        <div>
                            <h2 class="font-semibold text-teal-200">للإدارة</h2>
                            <p class="mt-2 text-sm text-teal-100/80">هيكل أكاديمي، تقارير، وإيقاف أو إخفاء عند الحاجة — مش بوابة يومية لكل حساب.</p>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </body>
</html>
