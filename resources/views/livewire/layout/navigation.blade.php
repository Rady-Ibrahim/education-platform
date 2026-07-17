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
     * @return list<array{label: string, route: string, active: bool}>
     */
    public function roleLinks(): array
    {
        $user = auth()->user();
        $role = $user?->primaryRole();

        return match ($role) {
            UserRole::Admin => [
                ['label' => 'لوحة الإدارة', 'route' => 'admin.dashboard', 'active' => request()->routeIs('admin.dashboard')],
                ['label' => 'الهيكل الأكاديمي', 'route' => 'admin.academic', 'active' => request()->routeIs('admin.academic')],
                ['label' => 'المدفوعات', 'route' => 'admin.payments', 'active' => request()->routeIs('admin.payments')],
            ],
            UserRole::Teacher => [
                ['label' => 'المكتب', 'route' => 'teacher.dashboard', 'active' => request()->routeIs('teacher.dashboard')],
                ['label' => 'الطلاب', 'route' => 'teacher.students', 'active' => request()->routeIs('teacher.students*')],
                ['label' => 'الدروس', 'route' => 'teacher.lessons', 'active' => request()->routeIs('teacher.lessons')],
                ['label' => 'الامتحانات', 'route' => 'teacher.exams', 'active' => request()->routeIs('teacher.exams')],
                ['label' => 'المدفوعات', 'route' => 'teacher.payments', 'active' => request()->routeIs('teacher.payments')],
            ],
            UserRole::Student => [
                ['label' => 'لوحتي', 'route' => 'student.dashboard', 'active' => request()->routeIs('student.dashboard')],
                ['label' => 'الدروس', 'route' => 'student.lessons', 'active' => request()->routeIs('student.lessons')],
                ['label' => 'الامتحانات', 'route' => 'student.exams', 'active' => request()->routeIs('student.exams')],
                ['label' => 'الاشتراكات', 'route' => 'student.subscriptions', 'active' => request()->routeIs('student.subscriptions')],
                ['label' => 'الشهادات', 'route' => 'student.certificates', 'active' => request()->routeIs('student.certificates*')],
            ],
            UserRole::Parent => [
                ['label' => 'لوحة ولي الأمر', 'route' => 'parent.dashboard', 'active' => request()->routeIs('parent.*')],
            ],
            default => [
                ['label' => 'الرئيسية', 'route' => 'dashboard', 'active' => request()->routeIs('dashboard')],
            ],
        };
    }
}; ?>

<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center gap-2">
                    <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                        <span class="hidden sm:inline text-lg font-semibold text-teal-800">{{ config('app.name', 'سنتر') }}</span>
                    </a>
                </div>

                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    @foreach ($this->roleLinks() as $link)
                        <x-nav-link :href="route($link['route'])" :active="$link['active']" wire:navigate>
                            {{ $link['label'] }}
                        </x-nav-link>
                    @endforeach
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6 gap-2">
                <livewire:shared.notification-bell />

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile')" wire:navigate>الملف الشخصي</x-dropdown-link>
                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link>تسجيل الخروج</x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @foreach ($this->roleLinks() as $link)
                <x-responsive-nav-link :href="route($link['route'])" :active="$link['active']" wire:navigate>
                    {{ $link['label'] }}
                </x-responsive-nav-link>
            @endforeach
        </div>

        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                <div class="font-medium text-sm text-gray-500">{{ auth()->user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile')" wire:navigate>الملف الشخصي</x-responsive-nav-link>
                <button wire:click="logout" class="w-full text-start">
                    <x-responsive-nav-link>تسجيل الخروج</x-responsive-nav-link>
                </button>
            </div>
        </div>
    </div>
</nav>
