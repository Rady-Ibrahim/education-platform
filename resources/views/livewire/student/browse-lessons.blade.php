<div
    class="space-y-5"
    x-data="{
        listenForProgress() {
            window.addEventListener('message', (e) => {
                if (!e.data || e.data.type !== 'ontimeupdate') return;
                const pct  = Math.round((e.data.currentTime / e.data.duration) * 100);
                const secs = Math.round(e.data.currentTime);
                // Auto-complete at 85 %
                if (pct >= 85) {
                    $wire.markComplete();
                } else if (pct % 10 === 0 && pct > 0) {
                    $wire.trackVideoProgress(pct, secs);
                }
            });
        }
    }"
    x-init="listenForProgress()"
>

    {{-- ── Empty state: no subjects ── --}}
    @if ($subjects->isEmpty())
        <div class="empty-state py-16">
            <svg class="mx-auto mb-4 h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            <p class="text-base font-semibold text-ink">لا توجد مواد متاحة بعد</p>
            <p class="mt-1 text-sm text-ink-muted">انضم لمدرس وأكمل الاشتراك للوصول للدروس.</p>
            <a href="{{ route('teachers.index') }}" wire:navigate class="btn-brand mt-5">تصفّح المدرسين</a>
        </div>

    @else

        {{-- ── Subject picker + progress bar ── --}}
        <div class="flex flex-wrap items-end justify-between gap-4 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-soft sm:p-5">
            <div class="min-w-0 flex-1">
                <x-input-label value="المادة" />
                <select wire:model.live="subjectId" class="mt-1.5 block w-full max-w-sm">
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}">
                            {{ $subject->grade?->stage?->name }} · {{ $subject->grade?->name }} · {{ $subject->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if ($totalLessons > 0)
                <div class="min-w-[180px] text-sm">
                    <div class="mb-1.5 flex items-center justify-between">
                        <span class="text-xs font-semibold text-ink-muted">تقدّمك</span>
                        <span class="text-xs font-bold text-brand-700">{{ $completedLessons }}/{{ $totalLessons }}</span>
                    </div>
                    <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                        <div
                            class="h-full rounded-full bg-gradient-to-l from-brand-500 to-brand-700 transition-all duration-500"
                            style="width: {{ $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0 }}%"
                        ></div>
                    </div>
                    @if ($completedLessons === $totalLessons && $totalLessons > 0)
                        <p class="mt-1 text-center text-[11px] font-bold text-emerald-600">✓ أكملت كل الدروس</p>
                    @endif
                </div>
            @endif
        </div>

        {{-- ── Access error ── --}}
        @if ($accessError)
            <div class="flex items-center gap-2.5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <svg class="h-4 w-4 shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m0 3.75h.008v.008H12v-.008zM10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                {{ $accessError }}
            </div>
        @endif

        {{-- ── Main layout: sidebar + content ── --}}
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-[320px_1fr]">

            {{-- ═══ Lesson sidebar (units + lessons list) ═══ --}}
            <aside class="space-y-2">
                @forelse ($units as $unit)
                    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-soft">
                        {{-- Unit header --}}
                        <div class="flex items-center justify-between gap-2 border-b border-slate-100 bg-slate-50/70 px-4 py-3">
                            <span class="text-sm font-bold text-ink">{{ $unit->name }}</span>
                            @php
                                $unitTotal     = $unit->lessons->count();
                                $unitCompleted = $unit->lessons->filter(fn ($l) => $l->progressRecords->first()?->is_completed)->count();
                            @endphp
                            <span class="shrink-0 rounded-md bg-brand-50 px-2 py-0.5 text-[11px] font-bold text-brand-700 ring-1 ring-brand-100">
                                {{ $unitCompleted }}/{{ $unitTotal }}
                            </span>
                        </div>

                        {{-- Lessons list --}}
                        <ul class="divide-y divide-slate-100">
                            @foreach ($unit->lessons as $lesson)
                                @php
                                    $progress  = $lesson->progressRecords->first();
                                    $pct       = $progress?->percent ?? 0;
                                    $done      = $progress?->is_completed ?? false;
                                    $active    = $lessonId === $lesson->id;
                                    $locked    = ! $lesson->accessible;
                                @endphp
                                <li>
                                    <button
                                        type="button"
                                        wire:click="{{ $locked ? '' : 'selectLesson('.$lesson->id.')' }}"
                                        @disabled($locked)
                                        @class([
                                            'group w-full px-4 py-3 text-start transition duration-200',
                                            'bg-brand-700 text-white' => $active,
                                            'cursor-not-allowed opacity-50' => $locked,
                                            'hover:bg-brand-50' => ! $active && ! $locked,
                                        ])
                                    >
                                        <div class="flex items-start gap-2.5">
                                            {{-- Status icon --}}
                                            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center">
                                                @if ($locked)
                                                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                                @elseif ($done)
                                                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500">
                                                        <svg class="h-3 w-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                    </span>
                                                @elseif ($pct > 0)
                                                    <svg class="h-5 w-5 text-brand-400" viewBox="0 0 20 20" fill="currentColor">
                                                        <circle cx="10" cy="10" r="10" fill="currentColor" opacity="0.15"/>
                                                        <path d="M10 5v5l3 3" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round"/>
                                                    </svg>
                                                @else
                                                    <span class="h-4 w-4 rounded-full border-2 {{ $active ? 'border-white' : 'border-slate-300' }}"></span>
                                                @endif
                                            </span>

                                            <span class="min-w-0 flex-1">
                                                <span @class([
                                                    'block text-sm font-semibold leading-snug',
                                                    'text-white' => $active,
                                                    'text-ink'   => ! $active,
                                                ])>{{ $lesson->title }}</span>

                                                <span class="mt-1 flex flex-wrap items-center gap-2">
                                                    {{-- Type badge --}}
                                                    <span @class([
                                                        'inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[10px] font-semibold',
                                                        'bg-white/20 text-white' => $active,
                                                        'bg-slate-100 text-ink-muted' => ! $active,
                                                    ])>
                                                        @switch($lesson->type->value)
                                                            @case('video')  🎬 فيديو @break
                                                            @case('live')   📡 Meet  @break
                                                            @case('mixed')  🎬 فيديو + نص @break
                                                            @default        📄 نص
                                                        @endswitch
                                                    </span>

                                                    {{-- Progress % for in-progress --}}
                                                    @if ($pct > 0 && ! $done)
                                                        <span @class([
                                                            'text-[10px] font-bold',
                                                            'text-white/70' => $active,
                                                            'text-brand-600' => ! $active,
                                                        ])>{{ $pct }}%</span>
                                                    @endif

                                                    {{-- Attachments indicator --}}
                                                    @if ($lesson->accessible && $lesson->attachments->isNotEmpty())
                                                        <span @class([
                                                            'text-[10px]',
                                                            'text-white/60' => $active,
                                                            'text-ink-muted' => ! $active,
                                                        ])>📎 {{ $lesson->attachments->count() }}</span>
                                                    @endif
                                                </span>

                                                {{-- Progress bar for in-progress lessons --}}
                                                @if ($pct > 0 && ! $done)
                                                    <div class="mt-1.5 h-1 w-full overflow-hidden rounded-full {{ $active ? 'bg-white/20' : 'bg-slate-200' }}">
                                                        <div class="h-full rounded-full {{ $active ? 'bg-white' : 'bg-brand-500' }}" style="width: {{ $pct }}%"></div>
                                                    </div>
                                                @endif
                                            </span>
                                        </div>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @empty
                    <div class="empty-state py-8">
                        <p class="text-sm text-ink-muted">لا توجد دروس منشورة في هذه المادة بعد.</p>
                    </div>
                @endforelse
            </aside>

            {{-- ═══ Lesson content area ═══ --}}
            <main class="min-h-[480px]">
                @if (! $lessonId || ! $current)
                    {{-- Empty placeholder --}}
                    <div class="flex min-h-[480px] flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-white/60 text-center">
                        <svg class="mb-4 h-14 w-14 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-base font-semibold text-ink">اختر درسًا للبدء</p>
                        <p class="mt-1 text-sm text-ink-muted">اضغط على أي درس من القائمة على اليمين.</p>
                    </div>

                @else
                    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-soft" wire:key="lesson-{{ $current->id }}">

                        {{-- Lesson header --}}
                        <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold text-ink-muted">{{ $current->unit?->name }}</p>
                                    <h2 class="mt-0.5 text-lg font-bold text-ink">{{ $current->title }}</h2>
                                </div>
                                @php $prog = $current->progressRecords->first(); @endphp
                                @if ($prog?->is_completed)
                                    <span class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 ring-1 ring-emerald-100">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        مكتمل
                                    </span>
                                @elseif ($prog?->percent > 0)
                                    <span class="inline-flex items-center gap-1.5 rounded-xl bg-brand-50 px-3 py-1.5 text-xs font-bold text-brand-700 ring-1 ring-brand-100">
                                        {{ $prog->percent }}% مكتمل
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="p-5 sm:p-6">
                            <div class="space-y-5">

                                {{-- ── Live lesson / Google Meet ── --}}
                                @if ($current->hasMeeting())
                                    @php
                                        $scheduledAt = $current->scheduled_at?->timezone(config('app.timezone', 'Africa/Cairo'));
                                        $now         = now()->timezone(config('app.timezone', 'Africa/Cairo'));
                                        $isLive      = $scheduledAt && $now->between($scheduledAt, $scheduledAt->copy()->addHours(2));
                                        $isFuture    = $scheduledAt && $now->lt($scheduledAt);
                                        $isPast      = $scheduledAt && $now->gt($scheduledAt->copy()->addHours(2));
                                        $isMeet      = str_contains($current->meeting_url ?? '', 'meet.google.com');
                                    @endphp

                                    <div @class([
                                        'overflow-hidden rounded-2xl border p-5',
                                        'border-emerald-300 bg-gradient-to-br from-emerald-50 to-white' => $isLive,
                                        'border-[#1A73E8]/30 bg-gradient-to-br from-[#F8FBFF] to-white' => ! $isLive,
                                    ])>
                                        {{-- Header --}}
                                        <div class="flex flex-wrap items-start justify-between gap-4">
                                            <div class="flex items-center gap-3">
                                                {{-- Google Meet logo or generic --}}
                                                @if ($isMeet)
                                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white shadow-soft ring-1 ring-[#1A73E8]/20">
                                                        <svg viewBox="0 0 87.5 75" class="h-6 w-6" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M50 31.25v12.5H37.5v-12.5H50z" fill="#00832D"/>
                                                            <path d="M50 43.75v12.5l12.5-6.25V37.5L50 43.75z" fill="#0066DA"/>
                                                            <path d="M37.5 43.75H25v12.5h12.5V43.75z" fill="#E94235"/>
                                                            <path d="M37.5 31.25H25v12.5h12.5v-12.5z" fill="#2684FC"/>
                                                            <path d="M50 31.25l12.5-6.25v12.5L50 43.75V31.25z" fill="#00AC47"/>
                                                            <path d="M37.5 56.25v-12.5L25 56.25h12.5z" fill="#FF6D00"/>
                                                        </svg>
                                                    </span>
                                                @else
                                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-700">
                                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 10l4.553-2.069A1 1 0 0121 8.87v6.26a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                                    </span>
                                                @endif

                                                <div>
                                                    <p class="font-bold text-ink">
                                                        {{ $isMeet ? 'حصة مباشرة — Google Meet' : 'حصة مباشرة' }}
                                                    </p>
                                                    @if ($isLive)
                                                        <span class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-700">
                                                            <span class="relative flex h-2 w-2">
                                                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                                                <span class="relative h-2 w-2 rounded-full bg-emerald-500"></span>
                                                            </span>
                                                            الحصة جارية الآن
                                                        </span>
                                                    @elseif ($isFuture)
                                                        <span class="text-xs text-ink-muted">موعد قادم</span>
                                                    @elseif ($isPast)
                                                        <span class="text-xs text-ink-muted">انتهت الحصة</span>
                                                    @else
                                                        <span class="text-xs text-ink-muted">حصة مباشرة</span>
                                                    @endif
                                                </div>
                                            </div>

                                            {{-- Join button --}}
                                            <a
                                                href="{{ $current->meeting_url }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                @class([
                                                    'inline-flex items-center gap-2 rounded-xl px-5 py-3 text-sm font-bold shadow-soft transition duration-200 active:scale-[0.97]',
                                                    'bg-emerald-600 text-white hover:bg-emerald-700' => $isLive,
                                                    'bg-[#1A73E8] text-white hover:bg-[#1557B0]' => ! $isLive && $isMeet,
                                                    'bg-brand-700 text-white hover:bg-brand-800' => ! $isLive && ! $isMeet,
                                                    'opacity-50 cursor-not-allowed pointer-events-none' => $isPast,
                                                ])
                                            >
                                                @if ($isMeet)
                                                    {{-- Meet camera icon --}}
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.069A1 1 0 0121 8.87v6.26a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                                @else
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                @endif
                                                {{ $isPast ? 'انتهت الحصة' : ($isMeet ? 'الانضمام عبر Google Meet' : 'دخول الحصة') }}
                                            </a>
                                        </div>

                                        {{-- Scheduled time + countdown --}}
                                        @if ($scheduledAt)
                                            <div class="mt-4 flex flex-wrap items-center gap-4 rounded-xl border border-white/80 bg-white/60 px-4 py-3">
                                                <div class="flex items-center gap-2 text-sm">
                                                    <svg class="h-4 w-4 shrink-0 text-ink-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                    <span class="font-semibold text-ink">{{ $scheduledAt->isoFormat('dddd، D MMMM YYYY') }}</span>
                                                </div>
                                                <div class="flex items-center gap-2 text-sm">
                                                    <svg class="h-4 w-4 shrink-0 text-ink-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    <span class="font-semibold text-ink">{{ $scheduledAt->format('H:i') }}</span>
                                                </div>

                                                {{-- Countdown (Alpine) --}}
                                                @if ($isFuture)
                                                    <div
                                                        x-data="{
                                                            target: new Date('{{ $scheduledAt->toIso8601String() }}'),
                                                            days: 0, hours: 0, mins: 0, secs: 0,
                                                            init() {
                                                                this.tick();
                                                                setInterval(() => this.tick(), 1000);
                                                            },
                                                            tick() {
                                                                const diff = Math.max(0, Math.floor((this.target - Date.now()) / 1000));
                                                                this.days  = Math.floor(diff / 86400);
                                                                this.hours = Math.floor((diff % 86400) / 3600);
                                                                this.mins  = Math.floor((diff % 3600) / 60);
                                                                this.secs  = diff % 60;
                                                            }
                                                        }"
                                                        class="ms-auto flex items-center gap-1.5 text-xs font-bold text-brand-700"
                                                    >
                                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                        <span>تبدأ بعد</span>
                                                        <span x-show="days > 0" x-text="days + ' يوم'" class="rounded-md bg-brand-100 px-1.5 py-0.5 tabular-nums"></span>
                                                        <span x-text="String(hours).padStart(2,'0')" class="rounded-md bg-brand-100 px-1.5 py-0.5 tabular-nums" dir="ltr"></span>
                                                        <span class="text-brand-400">:</span>
                                                        <span x-text="String(mins).padStart(2,'0')" class="rounded-md bg-brand-100 px-1.5 py-0.5 tabular-nums" dir="ltr"></span>
                                                        <span class="text-brand-400">:</span>
                                                        <span x-text="String(secs).padStart(2,'0')" class="rounded-md bg-brand-100 px-1.5 py-0.5 tabular-nums" dir="ltr"></span>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif

                                        {{-- Instructions --}}
                                        @if (! $isPast)
                                            <p class="mt-3 text-xs text-ink-muted">
                                                اضغط الزر للانضمام — سيُفتح الرابط في نافذة جديدة.
                                                @if ($isMeet) تأكد من تسجيل الدخول في حساب Google الخاص بك. @endif
                                            </p>
                                        @endif
                                    </div>

                                {{-- ── Video ── --}}
                                @elseif ($embedUrl)
                                    <div class="overflow-hidden rounded-2xl bg-brand-950 shadow-panel">
                                        <div class="aspect-video">
                                            <iframe
                                                src="{{ $embedUrl }}"
                                                class="h-full w-full"
                                                allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture"
                                                allowfullscreen
                                                id="bunny-player-{{ $current->id }}"
                                            ></iframe>
                                        </div>
                                    </div>

                                @elseif ($current->hasVideo())
                                    {{-- Video exists but Bunny not configured --}}
                                    <div class="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                                        <svg class="h-5 w-5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        الفيديو محمي — إعدادات المشغّل غير مكتملة حالياً. تواصل مع إدارة المنصة.
                                    </div>
                                @endif

                                {{-- ── Text body ── --}}
                                @if ($current->body)
                                    <div class="prose prose-sm max-w-none whitespace-pre-line leading-relaxed text-ink">
                                        {!! nl2br(e($current->body)) !!}
                                    </div>
                                @endif

                                {{-- ── Attachments ── --}}
                                @if ($current->attachments->where('is_downloadable', true)->isNotEmpty())
                                    <div class="rounded-2xl border border-slate-200/80 bg-slate-50/60 p-4">
                                        <h3 class="mb-3 text-xs font-bold uppercase tracking-wide text-ink-muted">
                                            مرفقات الدرس
                                        </h3>
                                        <ul class="space-y-2">
                                            @foreach ($current->attachments->where('is_downloadable', true) as $att)
                                                <li class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-3.5 py-3 transition hover:border-brand-200 hover:shadow-soft">
                                                    {{-- File type icon --}}
                                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                                                        @php $ext = strtolower(pathinfo($att->name, PATHINFO_EXTENSION)); @endphp
                                                        @if ($ext === 'pdf')
                                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                        @elseif (in_array($ext, ['jpg','jpeg','png','webp']))
                                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                        @else
                                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                        @endif
                                                    </span>
                                                    <span class="min-w-0 flex-1">
                                                        <span class="block truncate text-sm font-medium text-ink">{{ $att->name }}</span>
                                                        @if ($att->size)
                                                            <span class="text-xs text-ink-muted">{{ round($att->size / 1024) }} KB</span>
                                                        @endif
                                                    </span>
                                                    @if (! empty($attachmentLinks[$att->id]))
                                                        <a href="{{ $attachmentLinks[$att->id] }}"
                                                            download
                                                            class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-brand-700 transition hover:border-brand-200 hover:bg-brand-50">
                                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                                            تحميل
                                                        </a>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                {{-- ── Mark complete button (shown only when not done) ── --}}
                                @php $currentProg = $current->progressRecords->first(); @endphp
                                @if (! ($currentProg?->is_completed))
                                    <div class="flex justify-end border-t border-slate-100 pt-4">
                                        <button
                                            type="button"
                                            wire:click="markComplete"
                                            wire:loading.attr="disabled"
                                            class="btn-brand"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span wire:loading.remove wire:target="markComplete">إكمال الدرس</span>
                                            <span wire:loading wire:target="markComplete">جاري الحفظ…</span>
                                        </button>
                                    </div>
                                @else
                                    <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-4 text-sm font-semibold text-emerald-600">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        أكملت هذا الدرس
                                    </div>
                                @endif

                            </div>
                        </div>
                    </div>
                @endif
            </main>
        </div>

    @endif
</div>
