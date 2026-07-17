<div class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
    <div class="max-w-2xl">
        <p class="text-sm font-semibold text-teal-700">{{ config('app.name', 'سنتر') }}</p>
        <h1 class="mt-2 text-3xl font-bold text-slate-900">المدرسون</h1>
        <p class="mt-2 text-slate-600">تصفّح المدرسين واشترك معهم قبل أو بعد التسجيل. الدفع كاش في السنتر أو فودافون كاش — غالبًا نهاية الشهر.</p>
    </div>

    <div class="mt-8 grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="search" value="بحث" />
            <x-text-input wire:model.live.debounce.300ms="search" id="search" class="mt-1 block w-full" placeholder="اسم المدرس أو التخصص" />
        </div>
        <div>
            <x-input-label for="subjectId" value="المادة" />
            <select wire:model.live="subjectId" id="subjectId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-600 focus:ring-teal-600">
                <option value="">الكل</option>
                @foreach ($subjects as $subject)
                    <option value="{{ $subject->id }}">
                        {{ $subject->name }}@if($subject->grade) — {{ $subject->grade->name }}@endif
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($teachers as $teacher)
            <a href="{{ route('teachers.show', $teacher->slug) }}" wire:navigate class="block border-b border-slate-200 pb-5 transition hover:border-teal-600">
                <h2 class="text-lg font-semibold text-slate-900">{{ $teacher->name }}</h2>
                @if ($teacher->headline)
                    <p class="mt-1 text-sm text-teal-800">{{ $teacher->headline }}</p>
                @endif
                <p class="mt-2 line-clamp-3 text-sm text-slate-600">{{ $teacher->bio ?: 'مدرس في السنتر.' }}</p>
                @if ($teacher->teachingSubjects->isNotEmpty())
                    <p class="mt-3 text-xs text-slate-500">
                        {{ $teacher->teachingSubjects->pluck('name')->take(4)->join(' · ') }}
                    </p>
                @endif
            </a>
        @empty
            <div class="sm:col-span-2 lg:col-span-3 rounded-lg border border-dashed border-slate-300 p-10 text-center text-slate-500">
                لا يوجد مدرسون ظاهرون حاليًا. اطلب من المدرس إكمال بروفايله وإظهار صفحته للعامة.
            </div>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $teachers->links() }}
    </div>
</div>
