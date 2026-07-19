<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'سنتر') }} — منصة السنتر التعليمي</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            @keyframes home-fade-up {
                from { opacity: 0; transform: translateY(18px); }
                to { opacity: 1; transform: translateY(0); }
            }
            @keyframes home-wash {
                0%, 100% { transform: scale(1) translate(0, 0); }
                50% { transform: scale(1.06) translate(-1.5%, 1%); }
            }
            .home-fade {
                animation: home-fade-up 0.7s cubic-bezier(0.22, 1, 0.36, 1) both;
            }
            .home-fade-delay-1 { animation-delay: 0.12s; }
            .home-fade-delay-2 { animation-delay: 0.24s; }
            .home-fade-delay-3 { animation-delay: 0.36s; }
            .home-wash {
                animation: home-wash 22s ease-in-out infinite;
            }
            @media (prefers-reduced-motion: reduce) {
                .home-fade, .home-wash { animation: none !important; }
            }
        </style>
    </head>
    <body class="font-sans text-white antialiased">
        <div class="relative min-h-screen overflow-hidden">
            {{-- Full-bleed atmosphere --}}
            <div class="absolute inset-0 bg-brand-950" aria-hidden="true">
                <div class="home-wash absolute -inset-[8%] bg-hero-wash opacity-95"></div>
                <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_20%_30%,rgba(74,160,179,0.28),transparent_45%),radial-gradient(ellipse_at_90%_70%,rgba(226,160,8,0.12),transparent_40%)]"></div>
                <div class="absolute inset-0 opacity-[0.07]" style="background-image: linear-gradient(rgba(255,255,255,.5) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.5) 1px, transparent 1px); background-size: 56px 56px;"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-brand-950 via-brand-950/20 to-brand-950/55"></div>
            </div>

            <div class="relative mx-auto flex min-h-screen max-w-6xl flex-col px-4 py-5 sm:px-6 sm:py-6">
                <header class="home-fade flex items-center justify-between gap-4">
                    <a href="{{ url('/') }}" class="flex items-center gap-2.5">
                        <span class="brand-mark h-9 w-9 text-base">س</span>
                        <span class="text-xl font-bold tracking-tight text-white sm:text-2xl">{{ config('app.name', 'سنتر') }}</span>
                    </a>
                    <nav class="flex items-center gap-1 text-sm font-medium sm:gap-2">
                        <a href="{{ route('teachers.index') }}" class="rounded-lg px-3 py-2 text-brand-100/85 transition hover:bg-white/10 hover:text-white">
                            المدرسون
                        </a>
                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-xl bg-white/15 px-3.5 py-2 text-white transition hover:bg-white/25">
                                لوحتي
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 text-brand-100/85 transition hover:bg-white/10 hover:text-white">
                                دخول
                            </a>
                        @endauth
                    </nav>
                </header>

                <main class="flex flex-1 flex-col justify-center py-14 sm:py-16">
                    <div class="max-w-2xl">
                        <h1 class="home-fade home-fade-delay-1 text-[clamp(3.25rem,12vw,6.5rem)] font-extrabold leading-[0.95] tracking-tight text-white">
                            {{ config('app.name', 'سنتر') }}
                        </h1>
                        <p class="home-fade home-fade-delay-2 mt-6 max-w-md text-base leading-relaxed text-brand-100/85 sm:text-lg">
                            دروس محمية، امتحانات، ومتابعة اشتراكك — ادفع كاش في السنتر أو فودافون كاش.
                        </p>

                        <div class="home-fade home-fade-delay-3 mt-10 flex flex-wrap items-center gap-3">
                            @auth
                                <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-xl bg-accent px-6 py-3.5 text-sm font-bold text-ink transition hover:bg-accent-dark hover:text-white">
                                    ادخل لوحتك
                                </a>
                                <a href="{{ route('teachers.index') }}" class="inline-flex items-center justify-center rounded-xl border border-white/25 bg-white/5 px-6 py-3.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/12">
                                    تصفّح المدرسين
                                </a>
                            @else
                                <a href="{{ route('teachers.index') }}" class="inline-flex items-center justify-center rounded-xl bg-accent px-6 py-3.5 text-sm font-bold text-ink transition hover:bg-accent-dark hover:text-white">
                                    تصفّح المدرسين
                                </a>
                                <a href="{{ route('register', ['role' => 'student']) }}" class="inline-flex items-center justify-center rounded-xl border border-white/25 bg-white/5 px-6 py-3.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/12">
                                    ابدأ التسجيل
                                </a>
                            @endauth
                        </div>

                        @guest
                            <p class="home-fade home-fade-delay-3 mt-5 text-sm text-brand-100/55">
                                مدرس؟
                                <a href="{{ route('register', ['role' => 'teacher']) }}" class="font-semibold text-brand-100/80 underline-offset-4 transition hover:text-white hover:underline">سجّل كمدرس</a>
                                <span class="mx-2 text-white/25">·</span>
                                ولي أمر؟
                                <a href="{{ route('register', ['role' => 'parent']) }}" class="font-semibold text-brand-100/80 underline-offset-4 transition hover:text-white hover:underline">سجّل كولي أمر</a>
                            </p>
                        @endguest
                    </div>
                </main>
            </div>
        </div>

        <section class="border-t border-brand-900/40 bg-brand-950 px-4 py-14 sm:px-6">
            <div class="mx-auto grid max-w-6xl gap-10 sm:grid-cols-3 sm:gap-12">
                <div>
                    <h2 class="text-sm font-bold tracking-wide text-accent-soft">للطالب</h2>
                    <p class="mt-3 text-sm leading-7 text-brand-100/70">انضم لمدرسك، تابع الدروس والامتحانات، واشترك بمكان واحد.</p>
                </div>
                <div>
                    <h2 class="text-sm font-bold tracking-wide text-accent-soft">لولي الأمر</h2>
                    <p class="mt-3 text-sm leading-7 text-brand-100/70">اربط ابنك بكوده، ادفع فودافون كاش، وتابع النتائج والمصاريف.</p>
                </div>
                <div>
                    <h2 class="text-sm font-bold tracking-wide text-accent-soft">للمدرس</h2>
                    <p class="mt-3 text-sm leading-7 text-brand-100/70">مكتب كامل: طلاب، تحصيل، مصاريف، دروس، وامتحانات.</p>
                </div>
            </div>
        </section>
    </body>
</html>
