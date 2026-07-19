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
@endphp

<div class="flex h-full min-h-screen flex-col px-4 py-5">
    <a href="{{ route('dashboard') }}" class="mb-7 flex items-center gap-2.5 px-1">
        <span class="brand-mark h-9 w-9">س</span>
        <span class="text-xl font-bold tracking-tight text-brand-900">{{ config('app.name', 'سنتر') }}</span>
    </a>

    <nav class="space-y-1">
        @foreach ($links as $link)
            <a
                href="{{ route($link['route']) }}"
                @class(['sidebar-link', 'sidebar-link-active' => $link['active']])
            >
                @include('partials.nav-icon', ['icon' => $link['icon'], 'active' => $link['active']])
                <span>{{ $link['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="mt-auto space-y-4 pt-8">
        @if ($primaryAction)
            <a href="{{ route($primaryAction['route']) }}" class="btn-accent w-full">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ $primaryAction['label'] }}
            </a>
        @endif
        <p class="px-1 text-[11px] leading-4 text-ink-muted">
            افتح أي صفحة في تاب جديد: كلك يمين → فتح في علامة تبويب جديدة
        </p>
    </div>
</div>
