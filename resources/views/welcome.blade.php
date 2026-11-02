<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'سنتر') }} — منصة السنتر التعليمي</title>
        <meta name="description" content="منصة {{ config('app.name', 'سنتر') }} التعليمية — دروس محمية، امتحانات، ومتابعة اشتراكك. ادفع كاش في السنتر أو فودافون كاش.">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            @keyframes home-fade-up {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            @keyframes home-wash {
                0%, 100% { transform: scale(1) translate(0, 0); }
                50% { transform: scale(1.07) translate(-1.5%, 1.2%); }
            }
            @keyframes float-card {
                0%, 100% { transform: translateY(0px) rotate(-1deg); }
                50% { transform: translateY(-10px) rotate(-1deg); }
            }
            .home-fade {
                animation: home-fade-up 0.75s cubic-bezier(0.22, 1, 0.36, 1) both;
            }
            .home-fade-delay-1 { animation-delay: 0.1s; }
            .home-fade-delay-2 { animation-delay: 0.22s; }
            .home-fade-delay-3 { animation-delay: 0.34s; }
            .home-fade-delay-4 { animation-delay: 0.46s; }
            .home-wash {
                animation: home-wash 24s ease-in-out infinite;
            }
            .float-card {
                animation: float-card 6s ease-in-out infinite;
            }
            @media (prefers-reduced-motion: reduce) {
                .home-fade, .home-wash, .float-card { animation: none !important; }
            }
        </style>
    </head>
    <body class="font-sans text-white antialiased">

        {{-- ═══════════════════════════════ HERO ═══════════════════════════════ --}}
        <div class="relative min-h-screen overflow-hidden">

            {{-- Atmosphere --}}
            <div class="absolute inset-0 bg-brand-950" aria-hidden="true">
                <div class="home-wash absolute -inset-[10%] bg-hero-wash opacity-90"></div>
                <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_18%_28%,rgba(74,160,179,0.30),transparent_42%),radial-gradient(ellipse_at_88%_68%,rgba(226,160,8,0.14),transparent_38%)]"></div>
                {{-- Subtle dot grid --}}
                <div class="absolute inset-0 opacity-[0.055]" style="background-image: radial-gradient(circle, rgba(255,255,255,0.7) 1px, transparent 1px); background-size: 36px 36px;"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-brand-950 via-brand-950/15 to-brand-950/50"></div>
            </div>

            <div class="relative mx-auto flex min-h-screen max-w-6xl flex-col px-4 py-5 sm:px-6 sm:py-6">

                {{-- ── Navbar ── --}}
                <header class="home-fade flex items-center justify-between gap-4">
                    <a href="{{ url('/') }}" class="flex items-center gap-3 group">
                        <span class="brand-mark h-10 w-10 text-[1.1rem] shadow-soft transition duration-300 group-hover:bg-brand-600">س</span>
                        <span class="text-xl font-bold tracking-tight text-white sm:text-2xl">{{ config('app.name', 'سنتر') }}</span>
                    </a>
                    <nav class="flex items-center gap-1 text-sm font-medium sm:gap-2">
                        <a href="{{ route('teachers.index') }}" class="rounded-xl px-3.5 py-2 text-brand-100/80 transition duration-200 hover:bg-white/10 hover:text-white">
                            المدرسون
                        </a>
                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-xl bg-white/15 px-4 py-2 font-semibold text-white ring-1 ring-white/20 transition duration-200 hover:bg-white/25">
                                لوحتي
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="rounded-xl px-3.5 py-2 text-brand-100/80 transition duration-200 hover:bg-white/10 hover:text-white">
                                دخول
                            </a>
                            <a href="{{ route('register', ['role' => 'student']) }}" class="rounded-xl bg-accent px-4 py-2 font-semibold text-ink transition duration-200 hover:bg-accent-dark hover:text-white">
                                تسجيل
                            </a>
                        @endauth
                    </nav>
                </header>

                {{-- ── Hero Content ── --}}
                <main class="flex flex-1 flex-col justify-center py-14 sm:py-16">
                    <div class="grid gap-12 lg:grid-cols-[1fr_auto] lg:items-center">
                        <div class="max-w-2xl">
                            {{-- Badge --}}
                            <div class="home-fade home-fade-delay-1 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/8 px-3.5 py-1.5 text-xs font-semibold text-brand-100/90 backdrop-blur-sm">
                                <span class="relative flex h-2 w-2">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-accent opacity-70"></span>
                                    <span class="relative inline-flex h-2 w-2 rounded-full bg-accent"></span>
                                </span>
                                منصة السنتر التعليمي
                            </div>

                            <h1 class="home-fade home-fade-delay-1 mt-5 text-[clamp(3rem,11vw,6rem)] font-extrabold leading-[0.92] tracking-tight text-white">
                                {{ config('app.name', 'سنتر') }}
                            </h1>

                            <p class="home-fade home-fade-delay-2 mt-6 max-w-md text-base leading-relaxed text-brand-100/80 sm:text-lg">
                                دروس محمية، امتحانات، ومتابعة اشتراكك — ادفع كاش في السنتر أو فودافون كاش.
                            </p>

                            {{-- CTAs --}}
                            <div class="home-fade home-fade-delay-3 mt-10 flex flex-wrap items-center gap-3">
                                @auth
                                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 rounded-xl bg-accent px-6 py-3.5 text-sm font-bold text-ink shadow-soft transition duration-200 hover:bg-accent-dark hover:text-white active:scale-[0.97]">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-8 9 8M5 10v9a1 1 0 001 1h4v-5h4v5h4a1 1 0 001-1v-9"/></svg>
                                        ادخل لوحتك
                                    </a>
                                    <a href="{{ route('teachers.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/20 bg-white/8 px-6 py-3.5 text-sm font-semibold text-white backdrop-blur-sm transition duration-200 hover:bg-white/15 active:scale-[0.97]">
                                        تصفّح المدرسين
                                    </a>
                                @else
                                    <a href="{{ route('teachers.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-accent px-6 py-3.5 text-sm font-bold text-ink shadow-soft transition duration-200 hover:bg-accent-dark hover:text-white active:scale-[0.97]">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 11-8 0 4 4 0 018 0zm8 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                        تصفّح المدرسين
                                    </a>
                                    <a href="{{ route('register', ['role' => 'student']) }}" class="inline-flex items-center gap-2 rounded-xl border border-white/20 bg-white/8 px-6 py-3.5 text-sm font-semibold text-white backdrop-blur-sm transition duration-200 hover:bg-white/15 active:scale-[0.97]">
                                        ابدأ التسجيل
                                    </a>
                                @endauth
                            </div>

                            @guest
                                <p class="home-fade home-fade-delay-4 mt-5 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-brand-100/50">
                                    <a href="{{ route('register', ['role' => 'teacher']) }}" class="font-semibold text-brand-100/75 transition hover:text-white hover:underline underline-offset-4">مدرس؟ سجّل هنا</a>
                                    <span class="text-white/20">·</span>
                                    <a href="{{ route('login') }}" class="font-semibold text-brand-100/75 transition hover:text-white hover:underline underline-offset-4">لدي حساب</a>
                                </p>
                            @endguest
                        </div>

                        {{-- ── Floating stats card (decorative) ── --}}
                        <div class="home-fade home-fade-delay-2 hidden lg:block">
                            <div class="float-card w-64 rounded-2xl border border-white/12 bg-white/8 p-5 backdrop-blur-md">
                                <div class="mb-4 flex items-center gap-2.5">
                                    <span class="brand-mark h-8 w-8 text-sm">س</span>
                                    <div>
                                        <div class="text-xs font-bold text-white/90">{{ config('app.name', 'سنتر') }}</div>
                                        <div class="text-[11px] text-white/50">منصتك التعليمية</div>
                                    </div>
                                </div>
                                <div class="space-y-2.5">
                                    <div class="flex items-center justify-between rounded-xl bg-white/8 px-3 py-2.5">
                                        <span class="text-xs text-white/70">دروس محمية</span>
                                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-400">
                                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between rounded-xl bg-white/8 px-3 py-2.5">
                                        <span class="text-xs text-white/70">امتحانات إلكترونية</span>
                                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-400">
                                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between rounded-xl bg-white/8 px-3 py-2.5">
                                        <span class="text-xs text-white/70">متابعة المدفوعات</span>
                                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-400">
                                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between rounded-xl bg-white/8 px-3 py-2.5">
                                        <span class="text-xs text-white/70">فودافون كاش</span>
                                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-400">
                                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>

                {{-- ── Scroll hint ── --}}
                <div class="home-fade home-fade-delay-4 flex justify-center pb-2">
                    <div class="flex flex-col items-center gap-1.5 opacity-40">
                        <span class="text-[11px] font-medium tracking-wide text-white/70">اكتشف المزيد</span>
                        <svg class="h-4 w-4 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════ FEATURES ═══════════════════════════════ --}}
        <section class="bg-white py-20 sm:py-24">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <div class="mb-14 text-center">
                    <p class="text-sm font-bold uppercase tracking-[0.12em] text-brand-600">لماذا {{ config('app.name', 'سنتر') }}؟</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-ink sm:text-4xl">كل اللي تحتاجه في مكان واحد</h2>
                    <p class="mx-auto mt-4 max-w-xl text-base leading-relaxed text-ink-muted">منصة متكاملة للمدرسين والطلاب وأولياء الأمور — من الحضور للمدفوعات للامتحانات.</p>
                </div>

                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {{-- Feature 1 --}}
                    <div class="group rounded-2xl border border-slate-200/80 bg-slate-50/60 p-6 transition duration-300 hover:-translate-y-1 hover:border-brand-200 hover:bg-white hover:shadow-card">
                        <div class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-brand-100 text-brand-700 transition duration-300 group-hover:bg-brand-700 group-hover:text-white">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 19.5A2.5 2.5 0 016.5 17H20M4 19.5V6.5A2.5 2.5 0 016.5 4H20v13H6.5A2.5 2.5 0 004 19.5z"/></svg>
                        </div>
                        <h3 class="text-base font-bold text-ink">دروس محمية</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink-muted">محتوى تعليمي حصري للطلاب المشتركين فقط. فيديوهات ومستندات مؤمّنة ومنظّمة.</p>
                    </div>

                    {{-- Feature 2 --}}
                    <div class="group rounded-2xl border border-slate-200/80 bg-slate-50/60 p-6 transition duration-300 hover:-translate-y-1 hover:border-brand-200 hover:bg-white hover:shadow-card">
                        <div class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-brand-100 text-brand-700 transition duration-300 group-hover:bg-brand-700 group-hover:text-white">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        </div>
                        <h3 class="text-base font-bold text-ink">امتحانات إلكترونية</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink-muted">بنك أسئلة، تصحيح تلقائي، ونتائج فورية. مع إمكانية رصد الدرجات الورقية يدويًا.</p>
                    </div>

                    {{-- Feature 3 --}}
                    <div class="group rounded-2xl border border-slate-200/80 bg-slate-50/60 p-6 transition duration-300 hover:-translate-y-1 hover:border-brand-200 hover:bg-white hover:shadow-card">
                        <div class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-brand-100 text-brand-700 transition duration-300 group-hover:bg-brand-700 group-hover:text-white">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        </div>
                        <h3 class="text-base font-bold text-ink">حضور وغياب</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink-muted">تسجيل الحضور لكل مجموعة بنقرة. سجلّات واضحة وإشعارات تلقائية لأولياء الأمور.</p>
                    </div>

                    {{-- Feature 4 --}}
                    <div class="group rounded-2xl border border-slate-200/80 bg-slate-50/60 p-6 transition duration-300 hover:-translate-y-1 hover:border-brand-200 hover:bg-white hover:shadow-card">
                        <div class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-accent/15 text-accent-dark transition duration-300 group-hover:bg-accent group-hover:text-ink">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 10h18M7 15h1m4 0h1M5 6h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z"/></svg>
                        </div>
                        <h3 class="text-base font-bold text-ink">تحصيل المصاريف</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink-muted">دفتر تحصيل شهري، قبول مدفوعات فودافون كاش بإثبات، ومتابعة المستحقات بسهولة.</p>
                    </div>

                    {{-- Feature 5 --}}
                    <div class="group rounded-2xl border border-slate-200/80 bg-slate-50/60 p-6 transition duration-300 hover:-translate-y-1 hover:border-brand-200 hover:bg-white hover:shadow-card">
                        <div class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-brand-100 text-brand-700 transition duration-300 group-hover:bg-brand-700 group-hover:text-white">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 10h8M8 14h5M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.116-3.72A7.8 7.8 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        </div>
                        <h3 class="text-base font-bold text-ink">تواصل مع أولياء الأمور</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink-muted">رسائل مباشرة، إشعارات الغياب، ومتابعة الدرجات والمدفوعات من لوحة واحدة.</p>
                    </div>

                    {{-- Feature 6 --}}
                    <div class="group rounded-2xl border border-slate-200/80 bg-slate-50/60 p-6 transition duration-300 hover:-translate-y-1 hover:border-brand-200 hover:bg-white hover:shadow-card">
                        <div class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-brand-100 text-brand-700 transition duration-300 group-hover:bg-brand-700 group-hover:text-white">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4M7 4h10a2 2 0 012 2v12l-7-3-7 3V6a2 2 0 012-2z"/></svg>
                        </div>
                        <h3 class="text-base font-bold text-ink">شهادات إنجاز</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink-muted">شهادات قابلة للتحقق لكل طالب يكمل متطلبات الكورس — رابط مشاركة فوري.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ═══════════════════════════════ ROLES ═══════════════════════════════ --}}
        <section class="border-t border-slate-100 bg-slate-50 py-20 sm:py-24">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <div class="mb-14 text-center">
                    <h2 class="text-3xl font-bold tracking-tight text-ink sm:text-4xl">لكل دور لوحته</h2>
                    <p class="mx-auto mt-4 max-w-lg text-base leading-relaxed text-ink-muted">واجهة مخصصة لكل مستخدم — بسيطة وسريعة ومركّزة على ما يحتاجه فعلاً.</p>
                </div>

                <div class="grid gap-6 sm:grid-cols-3">
                    {{-- Teacher --}}
                    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-card">
                        <div class="bg-hero-wash p-6">
                            <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white/15 text-white ring-1 ring-white/20">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422A12.083 12.083 0 0112 21.5a12.083 12.083 0 01-6.16-10.922L12 14z"/></svg>
                            </div>
                            <h3 class="mt-3 text-lg font-bold text-white">للمدرس</h3>
                            <p class="mt-1 text-sm text-white/70">مكتب كامل بمكان واحد</p>
                        </div>
                        <ul class="divide-y divide-slate-100 p-5 text-sm">
                            <li class="flex items-center gap-2.5 py-2.5 text-ink-muted">
                                <svg class="h-4 w-4 shrink-0 text-brand-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                إدارة الطلاب والمجموعات
                            </li>
                            <li class="flex items-center gap-2.5 py-2.5 text-ink-muted">
                                <svg class="h-4 w-4 shrink-0 text-brand-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                تسجيل الحضور والغياب
                            </li>
                            <li class="flex items-center gap-2.5 py-2.5 text-ink-muted">
                                <svg class="h-4 w-4 shrink-0 text-brand-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                تحصيل المصاريف الشهرية
                            </li>
                            <li class="flex items-center gap-2.5 py-2.5 text-ink-muted">
                                <svg class="h-4 w-4 shrink-0 text-brand-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                دروس وامتحانات إلكترونية
                            </li>
                        </ul>
                        <div class="border-t border-slate-100 p-5 pt-3">
                            <a href="{{ route('register', ['role' => 'teacher']) }}" class="btn-brand w-full">سجّل كمدرس</a>
                        </div>
                    </div>

                    {{-- Student --}}
                    <div class="overflow-hidden rounded-2xl border border-brand-200 bg-white shadow-card ring-1 ring-brand-200/50">
                        <div class="relative bg-hero-wash p-6">
                            <span class="absolute end-4 top-4 rounded-full bg-accent px-2.5 py-0.5 text-[11px] font-bold text-ink">الأكثر شيوعًا</span>
                            <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white/15 text-white ring-1 ring-white/20">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            </div>
                            <h3 class="mt-3 text-lg font-bold text-white">للطالب</h3>
                            <p class="mt-1 text-sm text-white/70">تعلّم وتابع تقدّمك</p>
                        </div>
                        <ul class="divide-y divide-slate-100 p-5 text-sm">
                            <li class="flex items-center gap-2.5 py-2.5 text-ink-muted">
                                <svg class="h-4 w-4 shrink-0 text-brand-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                متابعة الدروس والمحتوى
                            </li>
                            <li class="flex items-center gap-2.5 py-2.5 text-ink-muted">
                                <svg class="h-4 w-4 shrink-0 text-brand-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                حل الامتحانات ومعرفة النتائج
                            </li>
                            <li class="flex items-center gap-2.5 py-2.5 text-ink-muted">
                                <svg class="h-4 w-4 shrink-0 text-brand-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                إدارة الاشتراكات والدفع
                            </li>
                            <li class="flex items-center gap-2.5 py-2.5 text-ink-muted">
                                <svg class="h-4 w-4 shrink-0 text-brand-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                شهادات إنجاز رقمية
                            </li>
                        </ul>
                        <div class="border-t border-slate-100 p-5 pt-3">
                            <a href="{{ route('register', ['role' => 'student']) }}" class="btn-accent w-full">سجّل كطالب</a>
                        </div>
                    </div>

                    {{-- Parent --}}
                    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-card">
                        <div class="bg-hero-wash p-6">
                            <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white/15 text-white ring-1 ring-white/20">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 11-8 0 4 4 0 018 0zm8 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            </div>
                            <h3 class="mt-3 text-lg font-bold text-white">لولي الأمر</h3>
                            <p class="mt-1 text-sm text-white/70">تابع ابنك عن بُعد</p>
                        </div>
                        <ul class="divide-y divide-slate-100 p-5 text-sm">
                            <li class="flex items-center gap-2.5 py-2.5 text-ink-muted">
                                <svg class="h-4 w-4 shrink-0 text-brand-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                ربط الأبناء بكود الطالب
                            </li>
                            <li class="flex items-center gap-2.5 py-2.5 text-ink-muted">
                                <svg class="h-4 w-4 shrink-0 text-brand-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                دفع المصاريف فودافون كاش
                            </li>
                            <li class="flex items-center gap-2.5 py-2.5 text-ink-muted">
                                <svg class="h-4 w-4 shrink-0 text-brand-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                متابعة الدرجات والحضور
                            </li>
                            <li class="flex items-center gap-2.5 py-2.5 text-ink-muted">
                                <svg class="h-4 w-4 shrink-0 text-brand-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                رسائل مباشرة من المدرسين
                            </li>
                        </ul>
                        <div class="border-t border-slate-100 p-5 pt-3">
                            <p class="text-center text-xs text-ink-muted">ولي الأمر يُضاف بواسطة المدرس من لوحته.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ═══════════════════════════════ FOOTER ═══════════════════════════════ --}}
        <footer class="border-t border-brand-900/30 bg-brand-950">
            <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="lg:col-span-2">
                        <a href="{{ url('/') }}" class="flex items-center gap-2.5">
                            <span class="brand-mark h-9 w-9">س</span>
                            <span class="text-xl font-bold tracking-tight text-white">{{ config('app.name', 'سنتر') }}</span>
                        </a>
                        <p class="mt-3 max-w-xs text-sm leading-relaxed text-brand-100/55">
                            منصة السنتر التعليمي — تحصيل، دروس، امتحانات، ومتابعة الطلاب من مكان واحد.
                        </p>
                    </div>

                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-[0.1em] text-brand-100/40">روابط سريعة</h4>
                        <ul class="mt-4 space-y-2.5 text-sm">
                            <li><a href="{{ route('teachers.index') }}" class="text-brand-100/60 transition hover:text-white">تصفّح المدرسين</a></li>
                            <li><a href="{{ route('login') }}" class="text-brand-100/60 transition hover:text-white">تسجيل الدخول</a></li>
                            <li><a href="{{ route('register', ['role' => 'student']) }}" class="text-brand-100/60 transition hover:text-white">سجّل كطالب</a></li>
                            <li><a href="{{ route('register', ['role' => 'teacher']) }}" class="text-brand-100/60 transition hover:text-white">سجّل كمدرس</a></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-[0.1em] text-brand-100/40">الحساب</h4>
                        <ul class="mt-4 space-y-2.5 text-sm">
                            @auth
                                <li><a href="{{ route('dashboard') }}" class="text-brand-100/60 transition hover:text-white">لوحتي</a></li>
                                <li><a href="{{ route('profile') }}" class="text-brand-100/60 transition hover:text-white">الملف الشخصي</a></li>
                            @else
                                <li><a href="{{ route('login') }}" class="text-brand-100/60 transition hover:text-white">دخول</a></li>
                                <li><a href="{{ route('register') }}" class="text-brand-100/60 transition hover:text-white">إنشاء حساب</a></li>
                            @endauth
                        </ul>
                    </div>
                </div>

                <div class="mt-10 flex flex-col items-center justify-between gap-4 border-t border-white/8 pt-8 text-xs text-brand-100/35 sm:flex-row">
                    <p>© {{ date('Y') }} {{ config('app.name', 'سنتر') }} — جميع الحقوق محفوظة</p>
                    <p>منصة السنتر التعليمي</p>
                </div>
            </div>
        </footer>
    </body>
</html>
