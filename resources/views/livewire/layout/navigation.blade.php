<?php

use App\Enums\UserRole;
use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }

    /**
     * @return list<array{label: string, route: string, active: bool, icon: string}>
     */
    public function roleLinks(): array
    {
        $role = auth()->user()?->primaryRole();

        return match ($role) {
            UserRole::Admin => [
                ['label' => 'لوحة التحكم', 'route' => 'admin.dashboard', 'active' => request()->routeIs('admin.dashboard'), 'icon' => 'home'],
                ['label' => 'الهيكل الأكاديمي', 'route' => 'admin.academic', 'active' => request()->routeIs('admin.academic'), 'icon' => 'academic'],
                ['label' => 'مدفوعات الطلاب', 'route' => 'admin.payments', 'active' => request()->routeIs('admin.payments'), 'icon' => 'payments'],
                ['label' => 'اشتراك المنصة', 'route' => 'admin.platform', 'active' => request()->routeIs('admin.platform'), 'icon' => 'platform'],
            ],
            UserRole::Teacher => [
                ['label' => 'لوحة التحكم', 'route' => 'teacher.dashboard', 'active' => request()->routeIs('teacher.dashboard'), 'icon' => 'home'],
                ['label' => 'الطلاب', 'route' => 'teacher.students', 'active' => request()->routeIs('teacher.students*'), 'icon' => 'students'],
                ['label' => 'الدروس', 'route' => 'teacher.lessons', 'active' => request()->routeIs('teacher.lessons'), 'icon' => 'lessons'],
                ['label' => 'الامتحانات', 'route' => 'teacher.exams', 'active' => request()->routeIs('teacher.exams'), 'icon' => 'exams'],
                ['label' => 'المدفوعات', 'route' => 'teacher.payments', 'active' => request()->routeIs('teacher.payments'), 'icon' => 'payments'],
                ['label' => 'اشتراك المنصة', 'route' => 'teacher.platform', 'active' => request()->routeIs('teacher.platform'), 'icon' => 'platform'],
            ],
            UserRole::Student => [
                ['label' => 'لوحتي', 'route' => 'student.dashboard', 'active' => request()->routeIs('student.dashboard'), 'icon' => 'home'],
                ['label' => 'الدروس', 'route' => 'student.lessons', 'active' => request()->routeIs('student.lessons'), 'icon' => 'lessons'],
                ['label' => 'الامتحانات', 'route' => 'student.exams', 'active' => request()->routeIs('student.exams'), 'icon' => 'exams'],
                ['label' => 'الاشتراكات', 'route' => 'student.subscriptions', 'active' => request()->routeIs('student.subscriptions'), 'icon' => 'payments'],
                ['label' => 'الشهادات', 'route' => 'student.certificates', 'active' => request()->routeIs('student.certificates*'), 'icon' => 'certificate'],
            ],
            UserRole::Parent => [
                ['label' => 'لوحة ولي الأمر', 'route' => 'parent.dashboard', 'active' => request()->routeIs('parent.*'), 'icon' => 'home'],
            ],
            default => [
                ['label' => 'الرئيسية', 'route' => 'dashboard', 'active' => request()->routeIs('dashboard'), 'icon' => 'home'],
            ],
        };
    }

    /**
     * @return array{label: string, route: string, params?: array}|null
     */
    public function primaryAction(): ?array
    {
        return match (auth()->user()?->primaryRole()) {
            UserRole::Teacher => ['label' => 'إضافة درس', 'route' => 'teacher.lessons'],
            UserRole::Student => ['label' => 'تصفّح المدرسين', 'route' => 'teachers.index'],
            UserRole::Admin => ['label' => 'الهيكل الأكاديمي', 'route' => 'admin.academic'],
            default => null,
        };
    }

    public function roleLabel(): string
    {
        $user = auth()->user();
        $role = $user?->primaryRole();

        return match ($role) {
            UserRole::Teacher => $user->headline ?: 'مدرس',
            UserRole::Student => 'طالب',
            UserRole::Parent => 'ولي أمر',
            UserRole::Admin => 'مدير النظام',
            default => 'مستخدم',
        };
    }
}; ?>

<div class="flex h-full min-h-screen flex-col px-4 py-5">
    <a href="{{ route('dashboard') }}" wire:navigate class="mb-8 px-2 text-2xl font-bold tracking-tight text-brand-900">
        {{ config('app.name', 'سنتر') }}
    </a>

    <nav class="space-y-1">
        @foreach ($this->roleLinks() as $link)
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

    <div class="mt-auto space-y-4 pt-8">
        @if ($action = $this->primaryAction())
            <a href="{{ route($action['route'], $action['params'] ?? []) }}" wire:navigate class="btn-accent w-full">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ $action['label'] }}
            </a>
        @endif

        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-700 text-sm font-bold text-white">
                    {{ mb_substr(auth()->user()->name, 0, 1) }}
                </div>
                <div class="min-w-0">
                    <div class="truncate text-sm font-bold text-ink" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                    <div class="truncate text-xs text-ink-muted">{{ $this->roleLabel() }}</div>
                </div>
            </div>
        </div>

        <div class="hidden items-center justify-between gap-2 px-1 lg:flex">
            <livewire:shared.notification-bell />
            <a href="{{ route('profile') }}" wire:navigate class="text-xs font-medium text-ink-muted hover:text-brand-800">الملف الشخصي</a>
        </div>

        <button type="button" wire:click="logout" class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-50">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            تسجيل الخروج
        </button>
    </div>
</div>
