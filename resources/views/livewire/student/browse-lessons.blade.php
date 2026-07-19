<div class="space-y-5">
    @if (session('progress_status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('progress_status') }}
        </div>
    @endif

    @if ($subjects->isEmpty())
        <div class="empty-state">
            <p class="text-sm text-ink-muted">لا توجد مواد متاحة بعد. انضم لمدرس وانتظر موافقته، أو أكمل الاشتراك.</p>
            <a href="{{ route('teachers.index') }}" class="mt-4 inline-flex link-brand" target="_blank" rel="noopener">تصفّح المدرسين ↗</a>
        </div>
    @else
        <x-page-section title="المادة" subtitle="اختر المادة لعرض دروسها.">
            <div class="max-w-xl">
                <x-input-label value="المادة" />
                <select wire:model.live="subjectId" class="mt-1.5 block w-full">
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}">
                            {{ $subject->grade?->stage?->name }} / {{ $subject->grade?->name }} / {{ $subject->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </x-page-section>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <x-page-section title="قائمة الدروس">
                <div class="max-h-[32rem] space-y-2 overflow-y-auto pe-1">
                    @forelse ($lessons as $lesson)
                        @php $progress = $lesson->progressRecords->first(); @endphp
                        <button
                            type="button"
                            wire:click="selectLesson({{ $lesson->id }})"
                            class="w-full rounded-xl border px-3 py-3 text-start text-sm transition {{ $lessonId === $lesson->id ? 'border-brand-500 bg-brand-50 shadow-soft' : 'border-slate-200 bg-white hover:border-brand-200 hover:bg-brand-50/60' }}"
                        >
                            <div class="font-semibold text-ink">{{ $lesson->title }}</div>
                            <div class="mt-1 text-xs text-ink-muted">
                                {{ $lesson->unit?->name }}
                                @if ($progress)
                                    <span class="ms-1 font-medium text-brand-700">— {{ $progress->percent }}%</span>
                                @endif
                            </div>
                        </button>
                    @empty
                        <div class="empty-state !py-6">
                            <p class="text-sm text-ink-muted">لا توجد دروس منشورة.</p>
                        </div>
                    @endforelse
                </div>
            </x-page-section>

            <x-page-section
                class="lg:col-span-2"
                :title="$current?->title ?? 'محتوى الدرس'"
                :subtitle="$current ? null : 'اختر درسًا من القائمة للبدء.'"
            >
                @if ($current)
                    <div class="space-y-4">
                        @if ($current->hasMeeting())
                            <div class="rounded-2xl border border-brand-200 bg-brand-50 p-5">
                                <div class="text-sm font-semibold text-brand-900">حصة لايف</div>
                                @if ($current->scheduled_at)
                                    <p class="mt-1 text-sm text-brand-800">الموعد: {{ $current->scheduled_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</p>
                                @endif
                                <a href="{{ $current->meeting_url }}" target="_blank" rel="noopener noreferrer" class="btn-brand mt-4">
                                    دخول الحصة (زوم / ميت)
                                </a>
                            </div>
                        @elseif ($embedUrl)
                            <div class="aspect-video overflow-hidden rounded-xl bg-brand-950">
                                <iframe
                                    src="{{ $embedUrl }}"
                                    class="h-full w-full"
                                    allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture"
                                    allowfullscreen
                                ></iframe>
                            </div>
                        @elseif ($current->hasVideo())
                            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                الفيديو محمي، لكن إعدادات Bunny غير مكتملة على السيرفر حاليًا.
                            </div>
                        @endif

                        @if ($current->body)
                            <div class="prose max-w-none whitespace-pre-line text-sm text-ink">{{ $current->body }}</div>
                        @endif

                        @if ($current->attachments->where('is_downloadable', true)->isNotEmpty())
                            <div>
                                <h4 class="mb-2 text-sm font-semibold text-ink-muted">مرفقات</h4>
                                <ul class="space-y-2 text-sm">
                                    @foreach ($current->attachments->where('is_downloadable', true) as $attachment)
                                        <li class="rounded-xl border border-slate-200 px-3 py-2">
                                            @if (! empty($attachmentLinks[$attachment->id]))
                                                <a href="{{ $attachmentLinks[$attachment->id] }}" class="font-medium text-brand-700 hover:text-brand-900">
                                                    {{ $attachment->name }}
                                                </a>
                                                <span class="ms-1 text-xs text-ink-muted">(رابط موقّع مؤقت)</span>
                                            @else
                                                <span class="text-ink">{{ $attachment->name }}</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="flex flex-wrap gap-2 border-t border-slate-100 pt-4">
                            <x-secondary-button wire:click="updateProgress(50)">حفظ تقدم 50%</x-secondary-button>
                            <x-primary-button type="button" wire:click="updateProgress(100)">إكمال الدرس</x-primary-button>
                        </div>
                    </div>
                @else
                    <div class="flex min-h-48 items-center justify-center text-sm text-ink-muted">
                        اختر درسًا من القائمة للبدء.
                    </div>
                @endif
            </x-page-section>
        </div>
    @endif
</div>
