<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-10">

    {{-- Back breadcrumb --}}
    <a href="{{ route('teachers.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-ink-muted transition duration-200 hover:text-brand-700" wire:navigate>
        <svg class="h-4 w-4 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        كل المدرسين
    </a>

    {{-- Flash messages --}}
    @if (session('status'))
        <div class="mt-4 flex items-center gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            <svg class="h-4 w-4 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('status') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mt-4 flex items-center gap-2.5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <svg class="h-4 w-4 shrink-0 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('error') }}
        </div>
    @endif

    @php
        $cover = $teacher->coverUrl();
        $avatar = $teacher->avatarUrl();
    @endphp

    {{-- Profile card --}}
    <section class="mt-6 overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-card">

        {{-- Cover banner --}}
        <div class="relative aspect-[21/8] min-h-[160px] bg-brand-100 sm:min-h-[200px]">
            @if ($cover)
                <img src="{{ $cover }}" alt="{{ $teacher->name }}" class="h-full w-full object-cover">
            @else
                <div class="h-full w-full bg-gradient-to-br from-brand-400 via-brand-300 to-accent/40"></div>
            @endif
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/10 to-transparent"></div>
        </div>

        {{-- Header / Identity --}}
        <div class="relative px-5 pb-8 sm:px-8">
            <div class="-mt-12 flex items-end gap-4 sm:-mt-14">
                <div class="h-24 w-24 shrink-0 overflow-hidden rounded-2xl border-4 border-white bg-brand-100 shadow-card sm:h-28 sm:w-28">
                    @if ($avatar)
                        <img src="{{ $avatar }}" alt="" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-brand-200 to-brand-400 text-3xl font-bold text-white">
                            {{ mb_substr($teacher->name, 0, 1) }}
                        </div>
                    @endif
                </div>
                <div class="min-w-0 pb-2 pt-14 sm:pt-16">
                    <div class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2.5 py-0.5 text-[11px] font-bold text-brand-700 ring-1 ring-brand-100">
                        {{ config('app.name', 'سنتر') }}
                    </div>
                    <h1 class="mt-1 truncate text-2xl font-bold tracking-tight text-ink sm:text-3xl">{{ $teacher->name }}</h1>
                    @if ($teacher->headline)
                        <p class="mt-0.5 text-sm font-medium text-brand-700 sm:text-base">{{ $teacher->headline }}</p>
                    @endif
                </div>
            </div>

            {{-- Body --}}
            <div class="mt-7 grid gap-8 lg:grid-cols-[1.5fr_1fr]">

                {{-- Left: bio + subjects --}}
                <div class="space-y-6">
                    @if ($teacher->bio)
                        <div>
                            <h2 class="text-xs font-bold uppercase tracking-[0.1em] text-ink-muted">نبذة عن المدرس</h2>
                            <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-ink">{{ $teacher->bio }}</p>
                        </div>
                    @endif

                    @if ($teacher->teachingSubjects->isNotEmpty())
                        <div>
                            <h2 class="text-xs font-bold uppercase tracking-[0.1em] text-ink-muted">المواد الدراسية</h2>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($teacher->teachingSubjects as $subject)
                                    <span class="inline-flex items-center gap-1 rounded-xl bg-brand-50 px-3 py-1.5 text-sm font-semibold text-brand-900 ring-1 ring-brand-100">
                                        <svg class="h-3.5 w-3.5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422A12.083 12.083 0 0112 21.5a12.083 12.083 0 01-6.16-10.922L12 14z"/></svg>
                                        {{ $subject->name }}@if($subject->grade) <span class="text-brand-500">·</span> {{ $subject->grade->name }}@endif
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Right: plans + join --}}
                <aside class="space-y-4">

                    {{-- Subscription plans --}}
                    @if ($plans->isNotEmpty())
                        <div class="rounded-2xl border border-brand-100/80 bg-gradient-to-b from-brand-50/60 to-white p-5">
                            <h2 class="text-xs font-bold uppercase tracking-[0.1em] text-ink-muted">خطط الاشتراك</h2>
                            <ul class="mt-3 space-y-2.5">
                                @foreach ($plans as $plan)
                                    <li class="flex items-center justify-between gap-3 rounded-xl border border-white bg-white px-4 py-3 shadow-soft">
                                        <div>
                                            <div class="font-semibold text-ink">{{ $plan->name }}</div>
                                            @if ($plan->duration_days)
                                                <div class="mt-0.5 text-xs text-ink-muted">{{ $plan->duration_days }} يوم</div>
                                            @endif
                                        </div>
                                        <div class="shrink-0 text-lg font-bold text-brand-800">
                                            {{ number_format((float) $plan->price, 0) }}
                                            <span class="text-sm font-medium text-ink-muted">ج.م</span>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                            <p class="mt-3 text-[11px] leading-relaxed text-ink-muted">الدفع غالبًا نهاية الشهر: كاش عند المدرس، أو فودافون كاش من ولي الأمر.</p>
                        </div>
                    @endif

                    {{-- Vodafone Cash --}}
                    @if ($teacher->vodafone_cash_number)
                        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-soft">
                            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.1em] text-ink-muted">
                                <svg class="h-3.5 w-3.5 text-red-500" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="10"/></svg>
                                فودافون كاش
                            </div>
                            <div class="mt-2 font-mono text-2xl tracking-[0.12em] text-ink" dir="ltr">{{ $teacher->vodafone_cash_number }}</div>
                            @if ($teacher->payment_instructions)
                                <p class="mt-2 whitespace-pre-line text-xs leading-relaxed text-ink-muted">{{ $teacher->payment_instructions }}</p>
                            @endif
                        </div>
                    @endif

                    {{-- Join section --}}
                    <div class="rounded-2xl border border-brand-200/70 bg-white p-5 shadow-soft">
                        <h2 class="text-base font-bold text-ink">الانضمام للمدرس</h2>

                        @guest
                            <p class="mt-2 text-sm leading-relaxed text-ink-muted">سجّل كطالب ثم اطلب الانضمام، أو خلّي المدرس يضيفك من مكتبه.</p>
                            <div class="mt-4 flex flex-col gap-2">
                                <a href="{{ route('register', ['role' => 'student', 'join' => $teacher->slug]) }}" wire:navigate class="btn-brand w-full">
                                    سجّل كطالب وانضم
                                </a>
                                <a href="{{ route('login') }}" wire:navigate class="btn-ghost w-full">
                                    لدي حساب بالفعل
                                </a>
                            </div>
                        @else
                            @if ($isLinked)
                                <div class="mt-3 flex items-center gap-2 rounded-xl bg-emerald-50 px-3.5 py-3 text-sm font-medium text-emerald-800 ring-1 ring-emerald-100">
                                    <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    أنت منضم لهذا المدرس
                                </div>
                                <a href="{{ route('student.subscriptions') }}" wire:navigate class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-brand-700 hover:text-brand-900 hover:underline underline-offset-4">
                                    إدارة الاشتراكات والدفع
                                    <svg class="h-3.5 w-3.5 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            @elseif ($hasPendingJoin)
                                <div class="mt-3 flex items-center gap-2 rounded-xl bg-amber-50 px-3.5 py-3 text-sm font-medium text-amber-800 ring-1 ring-amber-100">
                                    <svg class="h-4 w-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    طلب الانضمام بانتظار موافقة المدرس
                                </div>
                            @elseif (auth()->user()->hasRole('student'))
                                <form wire:submit="requestJoin" class="mt-4 space-y-3">
                                    <div>
                                        <x-input-label for="joinMessage" value="رسالة للمدرس (اختياري)" />
                                        <x-text-input wire:model="joinMessage" id="joinMessage" class="mt-1 block w-full" placeholder="مثلاً: طالب في الصف الثالث الثانوي..." />
                                    </div>
                                    <x-primary-button type="submit" class="w-full justify-center">اطلب الانضمام</x-primary-button>
                                </form>
                            @elseif (auth()->user()->hasRole('parent'))
                                <p class="mt-2 text-sm leading-relaxed text-ink-muted">كولي أمر: اربط ابنك بكوده من لوحتك، أو اطلب من المدرس ربطك وإضافة الطالب.</p>
                                <a href="{{ route('parent.dashboard') }}" wire:navigate class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-brand-700 hover:text-brand-900 hover:underline underline-offset-4">
                                    لوحة ولي الأمر
                                    <svg class="h-3.5 w-3.5 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            @else
                                <p class="mt-2 text-sm text-ink-muted">الانضمام متاح لحسابات الطلاب فقط.</p>
                            @endif
                        @endguest
                    </div>
                </aside>
            </div>
        </div>
    </section>
</div>
