<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-10">
    <a href="{{ route('teachers.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-brand-700 transition hover:text-brand-900" wire:navigate>
        ← كل المدرسين
    </a>

    @if (session('status'))
        <div class="mt-4 rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    @php
        $cover = $teacher->coverUrl();
        $avatar = $teacher->avatarUrl();
    @endphp

    <section class="mt-6 overflow-hidden rounded-3xl border border-brand-100/80 bg-white shadow-panel">
        <div class="relative aspect-[21/9] min-h-[180px] bg-brand-100 sm:min-h-[220px]">
            @if ($cover)
                <img src="{{ $cover }}" alt="{{ $teacher->name }}" class="h-full w-full object-cover">
            @else
                <div class="h-full w-full bg-gradient-to-br from-brand-300 via-brand-100 to-accent/50"></div>
            @endif
            <div class="absolute inset-0 bg-gradient-to-t from-black/55 via-black/15 to-transparent"></div>
        </div>

        <div class="relative px-5 pb-8 sm:px-8">
            <div class="-mt-12 flex items-end gap-4 sm:-mt-14">
                <div class="h-24 w-24 shrink-0 overflow-hidden rounded-2xl border-4 border-white bg-brand-100 shadow-soft sm:h-28 sm:w-28">
                    @if ($avatar)
                        <img src="{{ $avatar }}" alt="" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full w-full items-center justify-center text-3xl font-bold text-brand-800">
                            {{ mb_substr($teacher->name, 0, 1) }}
                        </div>
                    @endif
                </div>
                <div class="min-w-0 pb-1 pt-12 sm:pt-14">
                    <p class="text-xs font-semibold text-brand-700">{{ config('app.name', 'سنتر') }}</p>
                    <h1 class="truncate text-2xl font-bold tracking-tight text-ink sm:text-3xl">{{ $teacher->name }}</h1>
                    @if ($teacher->headline)
                        <p class="mt-1 text-sm text-brand-800 sm:text-base">{{ $teacher->headline }}</p>
                    @endif
                </div>
            </div>

            <div class="mt-6 grid gap-8 lg:grid-cols-[1.4fr_1fr]">
                <div class="space-y-6">
                    <div>
                        <h2 class="text-sm font-semibold text-ink-muted">نبذة</h2>
                        <p class="mt-2 whitespace-pre-line text-ink">{{ $teacher->bio ?: 'لا توجد نبذة بعد.' }}</p>
                    </div>

                    @if ($teacher->teachingSubjects->isNotEmpty())
                        <div>
                            <h2 class="text-sm font-semibold text-ink-muted">المواد</h2>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($teacher->teachingSubjects as $subject)
                                    <span class="rounded-lg bg-brand-50 px-3 py-1.5 text-sm font-medium text-brand-900">
                                        {{ $subject->name }}@if($subject->grade) — {{ $subject->grade->name }}@endif
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <aside class="space-y-4">
                    @if ($plans->isNotEmpty())
                        <div class="rounded-2xl border border-brand-100 bg-brand-50/50 p-5">
                            <h2 class="text-sm font-semibold text-ink-muted">خطط الاشتراك</h2>
                            <ul class="mt-3 space-y-3">
                                @foreach ($plans as $plan)
                                    <li class="flex items-center justify-between gap-3 rounded-xl bg-white px-3 py-3 shadow-soft">
                                        <div>
                                            <div class="font-semibold text-ink">{{ $plan->name }}</div>
                                            @if ($plan->duration_days)
                                                <div class="text-xs text-ink-muted">{{ $plan->duration_days }} يوم</div>
                                            @endif
                                        </div>
                                        <div class="shrink-0 text-base font-bold text-brand-800">
                                            {{ number_format((float) $plan->price, 0) }} ج.م
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                            <p class="mt-3 text-xs leading-relaxed text-ink-muted">الدفع غالبًا نهاية الشهر: كاش عند المدرس، أو فودافون كاش من ولي الأمر.</p>
                        </div>
                    @endif

                    @if ($teacher->vodafone_cash_number)
                        <div class="rounded-2xl border border-brand-100 bg-white p-5 shadow-soft">
                            <div class="text-sm font-semibold text-ink-muted">فودافون كاش</div>
                            <div class="mt-1 font-mono text-xl tracking-wide text-ink">{{ $teacher->vodafone_cash_number }}</div>
                            @if ($teacher->payment_instructions)
                                <p class="mt-2 whitespace-pre-line text-sm text-ink-muted">{{ $teacher->payment_instructions }}</p>
                            @endif
                        </div>
                    @endif

                    <div class="rounded-2xl border border-brand-100 bg-white p-5 shadow-soft">
                        <h2 class="text-base font-bold text-ink">الانضمام للمدرس</h2>

                        @guest
                            <p class="mt-2 text-sm text-ink-muted">سجّل كطالب ثم اطلب الانضمام، أو خلّي المدرس يضيفك من مكتبه.</p>
                            <div class="mt-4 flex flex-col gap-2">
                                <a href="{{ route('register', ['role' => 'student', 'join' => $teacher->slug]) }}" class="inline-flex items-center justify-center rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800" wire:navigate>
                                    سجّل كطالب وانضم
                                </a>
                                <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl border border-brand-200 px-4 py-2.5 text-sm font-medium text-ink transition hover:bg-brand-50" wire:navigate>
                                    لدي حساب
                                </a>
                            </div>
                        @else
                            @if ($isLinked)
                                <p class="mt-2 text-sm text-green-700">أنت منضم لهذا المدرس.</p>
                                <a href="{{ route('student.subscriptions') }}" class="mt-3 inline-flex text-sm font-semibold text-brand-700 hover:underline" wire:navigate>إدارة الاشتراكات والدفع</a>
                            @elseif ($hasPendingJoin)
                                <p class="mt-2 text-sm text-amber-700">طلب الانضمام بانتظار موافقة المدرس.</p>
                            @elseif (auth()->user()->hasRole('student'))
                                <form wire:submit="requestJoin" class="mt-4 space-y-3">
                                    <div>
                                        <x-input-label for="joinMessage" value="رسالة للمدرس (اختياري)" />
                                        <x-text-input wire:model="joinMessage" id="joinMessage" class="mt-1 block w-full" />
                                    </div>
                                    <x-primary-button type="submit">اطلب الانضمام</x-primary-button>
                                </form>
                            @elseif (auth()->user()->hasRole('parent'))
                                <p class="mt-2 text-sm text-ink-muted">كولي أمر: اربط ابنك بكوده من لوحتك، أو اطلب من المدرس ربطك وإضافة الطالب من مكتبه.</p>
                                <a href="{{ route('parent.dashboard') }}" class="mt-3 inline-flex text-sm font-semibold text-brand-700 hover:underline" wire:navigate>لوحة ولي الأمر</a>
                            @else
                                <p class="mt-2 text-sm text-ink-muted">الانضمام متاح لحسابات الطلاب.</p>
                            @endif
                        @endguest
                    </div>
                </aside>
            </div>
        </div>
    </section>
</div>
