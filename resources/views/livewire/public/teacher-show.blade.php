<div class="mx-auto max-w-4xl px-4 py-10 sm:px-6">
    <a href="{{ route('teachers.index') }}" class="text-sm text-teal-700 hover:underline" wire:navigate>← كل المدرسين</a>

    @if (session('status'))
        <div class="mt-4 rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="mt-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="mt-6">
        <p class="text-sm font-semibold text-teal-700">{{ config('app.name', 'سنتر') }}</p>
        <h1 class="mt-1 text-3xl font-bold text-slate-900">{{ $teacher->name }}</h1>
        @if ($teacher->headline)
            <p class="mt-2 text-lg text-teal-800">{{ $teacher->headline }}</p>
        @endif
        <p class="mt-4 whitespace-pre-line text-slate-700">{{ $teacher->bio ?: 'لا توجد نبذة بعد.' }}</p>
    </div>

    @if ($teacher->teachingSubjects->isNotEmpty())
        <div class="mt-8">
            <h2 class="text-sm font-semibold text-slate-500">المواد</h2>
            <ul class="mt-2 space-y-1 text-slate-800">
                @foreach ($teacher->teachingSubjects as $subject)
                    <li>{{ $subject->name }}@if($subject->grade) — {{ $subject->grade->name }}@endif</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($plans->isNotEmpty())
        <div class="mt-8">
            <h2 class="text-sm font-semibold text-slate-500">خطط الاشتراك</h2>
            <ul class="mt-3 divide-y divide-slate-200 border-y border-slate-200">
                @foreach ($plans as $plan)
                    <li class="flex items-center justify-between gap-4 py-3">
                        <div>
                            <div class="font-medium">{{ $plan->name }}</div>
                            @if ($plan->duration_days)
                                <div class="text-xs text-slate-500">{{ $plan->duration_days }} يوم</div>
                            @endif
                        </div>
                        <div class="font-semibold text-teal-800">{{ number_format((float) $plan->price, 2) }} ج.م</div>
                    </li>
                @endforeach
            </ul>
            <p class="mt-2 text-sm text-slate-500">الدفع غالبًا نهاية الشهر: كاش عند المدرس في السنتر، أو فودافون كاش من الطالب/ولي الأمر.</p>
        </div>
    @endif

    @if ($teacher->vodafone_cash_number)
        <div class="mt-8 rounded-lg bg-teal-50 p-4 text-sm text-teal-950">
            <div class="font-semibold">فودافون كاش</div>
            <div class="mt-1 font-mono text-lg tracking-wide">{{ $teacher->vodafone_cash_number }}</div>
            @if ($teacher->payment_instructions)
                <p class="mt-2 whitespace-pre-line text-teal-900/80">{{ $teacher->payment_instructions }}</p>
            @endif
        </div>
    @endif

    <div class="mt-10 border-t border-slate-200 pt-8">
        <h2 class="text-lg font-semibold text-slate-900">الانضمام للمدرس</h2>

        @guest
            <p class="mt-2 text-sm text-slate-600">سجّل كطالب ثم اطلب الانضمام، أو خلّي المدرس يضيفك من مكتبه في السنتر.</p>
            <div class="mt-4 flex flex-wrap gap-3">
                <a href="{{ route('register', ['role' => 'student', 'join' => $teacher->slug]) }}" class="inline-flex items-center rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800" wire:navigate>
                    سجّل كطالب وانضم
                </a>
                <a href="{{ route('login') }}" class="inline-flex items-center rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50" wire:navigate>
                    لدي حساب
                </a>
            </div>
        @else
            @if ($isLinked)
                <p class="mt-2 text-sm text-green-700">أنت منضم لهذا المدرس.</p>
                <a href="{{ route('student.subscriptions') }}" class="mt-3 inline-flex text-sm font-medium text-teal-700 hover:underline" wire:navigate>إدارة الاشتراكات والدفع</a>
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
                <p class="mt-2 text-sm text-slate-600">كولي أمر: اربط ابنك بكوده من لوحتك، أو اطلب من المدرس ربطك وإضافة الطالب من مكتبه. الدفع فودافون كاش متاح بعد الربط.</p>
                <a href="{{ route('parent.dashboard') }}" class="mt-3 inline-flex text-sm font-medium text-teal-700 hover:underline" wire:navigate>لوحة ولي الأمر</a>
            @else
                <p class="mt-2 text-sm text-slate-600">الانضمام متاح لحسابات الطلاب.</p>
            @endif
        @endguest
    </div>
</div>
