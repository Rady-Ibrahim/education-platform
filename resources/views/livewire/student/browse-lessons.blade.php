<div class="space-y-6">
    @if (session('progress_status'))
        <div class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md p-3">
            {{ session('progress_status') }}
        </div>
    @endif

    @if ($subjects->isEmpty())
        <p class="text-sm text-gray-500">لا توجد مواد متاحة. انضم لمدرس أولاً وانتظر موافقته.</p>
    @else
        <div>
            <x-input-label value="المادة" />
            <select wire:model.live="subjectId" class="mt-1 block w-full md:w-1/2 border-gray-300 rounded-md shadow-sm">
                @foreach ($subjects as $subject)
                    <option value="{{ $subject->id }}">
                        {{ $subject->grade?->stage?->name }} / {{ $subject->grade?->name }} / {{ $subject->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="space-y-2">
                <h4 class="font-medium">الدروس</h4>
                @forelse ($lessons as $lesson)
                    @php $progress = $lesson->progressRecords->first(); @endphp
                    <button
                        type="button"
                        wire:click="selectLesson({{ $lesson->id }})"
                        class="w-full text-start border rounded-md px-3 py-2 text-sm {{ $lessonId === $lesson->id ? 'border-indigo-500 bg-indigo-50' : 'hover:bg-gray-50' }}"
                    >
                        <div class="font-medium">{{ $lesson->title }}</div>
                        <div class="text-gray-500">
                            {{ $lesson->unit?->name }}
                            @if ($progress)
                                — {{ $progress->percent }}%
                            @endif
                        </div>
                    </button>
                @empty
                    <p class="text-sm text-gray-500">لا توجد دروس منشورة.</p>
                @endforelse
            </div>

            <div class="lg:col-span-2 border rounded-lg p-4 space-y-4 min-h-64">
                @if ($current)
                    <h3 class="text-lg font-semibold">{{ $current->title }}</h3>

                    @if ($embedUrl)
                        <div class="aspect-video bg-black rounded-md overflow-hidden">
                            <iframe
                                src="{{ $embedUrl }}"
                                class="w-full h-full"
                                allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture"
                                allowfullscreen
                            ></iframe>
                        </div>
                    @elseif ($current->hasVideo())
                        <p class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-md p-3">
                            الفيديو محمي، لكن إعدادات Bunny غير مكتملة على السيرفر حاليًا.
                        </p>
                    @endif

                    @if ($current->body)
                        <div class="prose max-w-none text-sm text-gray-800 whitespace-pre-line">{{ $current->body }}</div>
                    @endif

                    @if ($current->attachments->where('is_downloadable', true)->isNotEmpty())
                        <div>
                            <h4 class="font-medium mb-2">مرفقات</h4>
                            <ul class="space-y-1 text-sm">
                                @foreach ($current->attachments->where('is_downloadable', true) as $attachment)
                                    <li>{{ $attachment->name }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="flex flex-wrap gap-2">
                        <x-secondary-button wire:click="updateProgress(50)">حفظ تقدم 50%</x-secondary-button>
                        <x-primary-button wire:click="updateProgress(100)">إكمال الدرس</x-primary-button>
                    </div>
                @else
                    <p class="text-sm text-gray-500">اختر درسًا من القائمة.</p>
                @endif
            </div>
        </div>
    @endif
</div>
