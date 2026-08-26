@php
    use App\Enums\UserRole;

    $user = auth()->user();
    $role = $user?->primaryRole();

    $links = match ($role) {
        UserRole::Admin => [
            ['label' => 'لوحة التحكم', 'route' => 'admin.dashboard', 'active' => request()->routeIs('admin.dashboard'), 'icon' => 'home'],
            ['label' => 'الحسابات', 'route' => 'admin.users', 'active' => request()->routeIs('admin.users'), 'icon' => 'students'],
            ['label' => 'الهيكل الأكاديمي', 'route' => 'admin.academic', 'active' => request()->routeIs('admin.academic'), 'icon' => 'academic'],
            ['label' => 'مدفوعات الطلاب', 'route' => 'admin.payments', 'active' => request()->routeIs('admin.payments'), 'icon' => 'payments'],
            ['label' => 'اشتراك المنصة', 'route' => 'admin.platform', 'active' => request()->routeIs('admin.platform'), 'icon' => 'platform'],
        ],
        UserRole::Teacher => [
            ['label' => 'لوحة التحكم', 'route' => 'teacher.dashboard', 'active' => request()->routeIs('teacher.dashboard'), 'icon' => 'home'],
            ['label' => 'المجموعات', 'route' => 'teacher.groups', 'active' => request()->routeIs('teacher.groups'), 'icon' => 'groups'],
            ['label' => 'الحضور', 'route' => 'teacher.attendance', 'active' => request()->routeIs('teacher.attendance'), 'icon' => 'attendance'],
            ['label' => 'الطلاب', 'route' => 'teacher.students', 'active' => request()->routeIs('teacher.students*'), 'icon' => 'students'],
            ['label' => 'رسائل أولياء الأمور', 'route' => 'teacher.messages', 'active' => request()->routeIs('teacher.messages'), 'icon' => 'messages'],
            ['label' => 'الدروس', 'route' => 'teacher.lessons', 'active' => request()->routeIs('teacher.lessons'), 'icon' => 'lessons'],
            ['label' => 'الامتحانات', 'route' => 'teacher.exams', 'active' => request()->routeIs('teacher.exams*'), 'icon' => 'exams'],
            ['label' => 'التحصيل', 'route' => 'teacher.payments', 'active' => request()->routeIs('teacher.payments'), 'icon' => 'payments'],
            ['label' => 'اشتراك المنصة', 'route' => 'teacher.platform', 'active' => request()->routeIs('teacher.platform'), 'icon' => 'platform'],
        ],
        UserRole::Student => [
            ['label' => 'لوحتي', 'route' => 'student.dashboard', 'active' => request()->routeIs('student.dashboard'), 'icon' => 'home'],
            ['label' => 'الدروس', 'route' => 'student.lessons', 'active' => request()->routeIs('student.lessons'), 'icon' => 'lessons'],
            ['label' => 'الامتحانات', 'route' => 'student.exams', 'active' => request()->routeIs('student.exams'), 'icon' => 'exams'],
            ['label' => 'الاشتراكات', 'route' => 'student.subscriptions', 'active' => request()->routeIs('student.subscriptions'), 'icon' => 'payments'],
            ['label' => 'أولياء الأمور', 'route' => 'student.parents', 'active' => request()->routeIs('student.parents'), 'icon' => 'students'],
            ['label' => 'الشهادات', 'route' => 'student.certificates', 'active' => request()->routeIs('student.certificates*'), 'icon' => 'certificate'],
        ],
        UserRole::Parent => [
            ['label' => 'لوحة ولي الأمر', 'route' => 'parent.dashboard', 'active' => request()->routeIs('parent.dashboard'), 'icon' => 'home'],
            ['label' => 'نتائج الامتحانات', 'route' => 'parent.exams', 'active' => request()->routeIs('parent.exams') || request()->routeIs('parent.children.exams'), 'icon' => 'exams'],
            ['label' => 'رسائل المدرسين', 'route' => 'parent.messages', 'active' => request()->routeIs('parent.messages'), 'icon' => 'messages'],
            ['label' => 'ربط ابن', 'route' => 'parent.link', 'active' => request()->routeIs('parent.link'), 'icon' => 'students'],
        ],
        default => [
            ['label' => 'الرئيسية', 'route' => 'dashboard', 'active' => request()->routeIs('dashboard'), 'icon' => 'home'],
        ],
    };

    $primaryAction = match ($role) {
        UserRole::Teacher => ['label' => 'إضافة طالب', 'route' => 'teacher.students.add'],
        UserRole::Student => ['label' => 'تصفّح المدرسين', 'route' => 'teachers.index'],
        UserRole::Admin => ['label' => 'الحسابات', 'route' => 'admin.users'],
        UserRole::Parent => ['label' => 'ربط ابن', 'route' => 'parent.link'],
        default => null,
    };

    $roleLabel = match ($role) {
        UserRole::Teacher => 'مدرس',
        UserRole::Student => 'طالب',
        UserRole::Parent => 'ولي أمر',
        UserRole::Admin => 'مدير النظام',
        default => 'مستخدم',
    };
@endphp

<div class="flex h-full min-h-screen flex-col" style="background: linear-gradient(180deg, #ffffff 0%, #F8FBFC 100%);">

    {{-- Logo area --}}
    <div class="border-b border-slate-100 px-5 py-5">
        <a href="{{ route('dashboard') }}" class="group flex items-center gap-3">
            <span class="brand-mark h-9 w-9 shadow-soft transition duration-200 group-hover:bg-brand-800">س</span>
            <div class="min-w-0">
                <span class="block text-[1.1rem] font-bold tracking-tight text-brand-900 leading-none">{{ config('app.name', 'سنتر') }}</span>
                <span class="mt-0.5 block text-[11px] font-medium text-ink-muted">{{ $roleLabel }}</span>
            </div>
        </a>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-0.5">
        @foreach ($links as $link)
            <a
                href="{{ route($link['route']) }}"
                wire:navigate
                @class(['sidebar-link', 'sidebar-link-active' => $link['active']])
            >
                @include('partials.nav-icon', ['icon' => $link['icon'], 'active' => $link['active']])
                <span>{{ $link['label'] }}</span>
            </a>
        @endforeach
    </nav>

    {{-- Bottom section --}}
    <div class="border-t border-slate-100 px-3 py-4 space-y-3">
        @if ($primaryAction)
            <a href="{{ route($primaryAction['route']) }}" wire:navigate class="btn-accent w-full">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ $primaryAction['label'] }}
            </a>
        @endif

        {{-- User mini-card --}}
        <div class="flex items-center gap-2.5 rounded-xl bg-slate-100/70 px-3 py-2.5">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-700 text-xs font-bold text-white">
                {{ mb_substr($user->name, 0, 1) }}
            </span>
            <div class="min-w-0 flex-1">
                <div class="truncate text-xs font-bold text-ink">{{ $user->name }}</div>
                <div class="truncate text-[11px] text-ink-muted">{{ $roleLabel }}</div>
            </div>
            <a href="{{ route('profile') }}" wire:navigate class="shrink-0 text-ink-muted transition hover:text-brand-700" title="الملف الشخصي">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </a>
        </div>
    </div>
</div>
