<div class="space-y-6">
    @if (session('progress_status'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('progress_status') }}
        </div>
    @endif

    @if ($subjects->isEmpty())
        <div class="rounded-2xl border border-dashed border-brand-200 bg-brand-50/40 px-6 py-10 text-center">
            <p class="text-sm text-ink-muted">لا توجد مواد متاحة بعد. انضم لمدرس وانتظر موافقته، أو أكمل الاشتراك.</p>
            <a href="{{ route('teachers.index') }}" class="mt-4 inline-flex link-brand" wire:navigate>تصفّح المدرسين</a>
        </div>
    @else
        <div class="max-w-xl">
            <x-input-label value="المادة" />
            <select wire:model.live="subjectId" class="mt-1 block w-full rounded-xl border-brand-200 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                @foreach ($subjects as $subject)
                    <option value="{{ $subject->id }}">
                        {{ $subject->grade?->stage?->name }} / {{ $subject->grade?->name }} / {{ $subject->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <div class="space-y-2">
                <h3 class="text-sm font-semibold text-ink-muted">قائمة الدروس</h3>
                <div class="max-h-[32rem] space-y-2 overflow-y-auto pe-1">
                    @forelse ($lessons as $lesson)
                        @php $progress = $lesson->progressRecords->first(); @endphp
                        <button
                            type="button"
                            wire:click="selectLesson({{ $lesson->id }})"
                            class="w-full rounded-xl border px-3 py-3 text-start text-sm transition {{ $lessonId === $lesson->id ? 'border-brand-500 bg-brand-50 shadow-soft' : 'border-brand-100 bg-white hover:border-brand-200 hover:bg-brand-50/60' }}"
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
                        <p class="rounded-xl border border-dashed border-brand-200 px-4 py-6 text-sm text-ink-muted">لا توجد دروس منشورة.</p>
                    @endforelse
                </div>
            </div>

            <div class="min-h-64 space-y-4 rounded-2xl border border-brand-100 bg-white p-5 shadow-soft lg:col-span-2">
                @if ($current)
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <h3 class="text-lg font-bold text-ink">{{ $current->title }}</h3>
                    </div>

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
                                    <li class="rounded-xl border border-brand-100 px-3 py-2">
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

                    <div class="flex flex-wrap gap-2 border-t border-brand-50 pt-4">
                        <x-secondary-button wire:click="updateProgress(50)">حفظ تقدم 50%</x-secondary-button>
                        <x-primary-button type="button" wire:click="updateProgress(100)">إكمال الدرس</x-primary-button>
                    </div>
                @else
                    <div class="flex h-full min-h-48 items-center justify-center text-sm text-ink-muted">
                        اختر درسًا من القائمة للبدء.
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
