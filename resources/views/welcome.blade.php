<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'سنتر') }} — منصة السنتر التعليمي</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-white antialiased">
        <div class="relative min-h-screen overflow-hidden">
            <div class="absolute inset-0">
                <img
                    src="{{ asset('images/home/hero.jpg') }}"
                    alt=""
                    class="h-full w-full scale-105 object-cover"
                >
                <div class="absolute inset-0 bg-brand-950/75"></div>
                <div class="absolute inset-0 bg-gradient-to-l from-brand-950/95 via-brand-950/70 to-brand-900/40"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-brand-950 via-transparent to-brand-950/50"></div>
            </div>

            <div class="relative mx-auto flex min-h-screen max-w-6xl flex-col px-4 py-6 sm:px-6 sm:py-8">
                <header class="flex items-center justify-between gap-4">
                    <a href="{{ url('/') }}" class="text-2xl font-bold tracking-tight text-white">
                        {{ config('app.name', 'سنتر') }}
                    </a>
                    <nav class="flex flex-wrap items-center gap-2 text-sm font-medium sm:gap-3">
                        <a href="{{ route('teachers.index') }}" class="rounded-lg px-2 py-1.5 text-brand-100/90 transition hover:bg-white/10 hover:text-white">المدرسون</a>
                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-xl bg-white/15 px-3 py-1.5 transition hover:bg-white/25">لوحتي</a>
                        @else
                            <a href="{{ route('login') }}" class="rounded-lg px-2 py-1.5 text-brand-100/90 transition hover:bg-white/10 hover:text-white">دخول</a>
                            <a href="{{ route('register', ['role' => 'student']) }}" class="rounded-xl bg-accent px-3 py-1.5 font-semibold text-ink transition hover:bg-accent-dark hover:text-white">سجّل كطالب</a>
                        @endauth
                    </nav>
                </header>

                <main class="flex flex-1 flex-col justify-center py-16 sm:py-20">
                    <div class="max-w-xl">
                        <p class="mb-4 text-sm font-semibold text-accent-soft">منصة السنتر التعليمي</p>
                        <h1 class="text-5xl font-bold leading-[1.12] tracking-tight sm:text-6xl lg:text-7xl">
                            {{ config('app.name', 'سنتر') }}
                        </h1>
                        <p class="mt-5 max-w-lg text-lg leading-relaxed text-brand-100/90">
                            انضم لمدرسك، تابع دروسك واشتراكاتك، وادفع كاش في السنتر أو فودافون كاش.
                        </p>
                        <div class="mt-9 flex flex-wrap gap-3">
                            @guest
                                <a href="{{ route('register', ['role' => 'student']) }}" class="rounded-xl bg-accent px-6 py-3.5 text-sm font-bold text-ink shadow-lg shadow-black/20 transition hover:bg-accent-dark hover:text-white">
                                    سجّل كطالب
                                </a>
                            @endguest
                            <a href="{{ route('teachers.index') }}" class="rounded-xl border border-white/30 bg-white/5 px-6 py-3.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/15">
                                تصفّح المدرسين
                            </a>
                            @guest
                                <a href="{{ route('register', ['role' => 'teacher']) }}" class="rounded-xl px-4 py-3.5 text-sm font-medium text-brand-100/80 transition hover:text-white">
                                    سجّل كمدرس
                                </a>
                            @endguest
                        </div>
                    </div>
                </main>

                <section class="grid gap-8 border-t border-white/15 py-8 sm:grid-cols-3 sm:gap-10 sm:py-10">
                    <div>
                        <h2 class="text-sm font-semibold text-accent-soft">للطالب</h2>
                        <p class="mt-2 text-sm leading-relaxed text-brand-100/75">سجّل، انضم لمدرسك، وتابع الدروس والامتحانات من مكان واحد.</p>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-accent-soft">لولي الأمر</h2>
                        <p class="mt-2 text-sm leading-relaxed text-brand-100/75">اربط حساب ابنك وادفع فودافون كاش بمتابعة واضحة للاشتراك.</p>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-accent-soft">للمدرس</h2>
                        <p class="mt-2 text-sm leading-relaxed text-brand-100/75">إدارة الطلاب والكاش والدروس والامتحانات من مكتب السنتر.</p>
                    </div>
                </section>
            </div>
        </div>
    </body>
</html>
