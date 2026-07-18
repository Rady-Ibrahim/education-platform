<x-app-layout>
    @php
        $user = auth()->user();
        $roleLabel = match ($user->primaryRole()) {
            \App\Enums\UserRole::Teacher => 'مدرس',
            \App\Enums\UserRole::Student => 'طالب',
            \App\Enums\UserRole::Parent => 'ولي أمر',
            \App\Enums\UserRole::Admin => 'مدير النظام',
            default => 'مستخدم',
        };
    @endphp

    <x-panel-page title="الملف الشخصي" subtitle="حدّث بياناتك وكلمة المرور، واضبط ظهورك العام إن كنت مدرسًا.">
        <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-gradient-to-l from-brand-700 via-brand-800 to-brand-950 text-white shadow-soft">
            <div class="flex flex-wrap items-center gap-5 px-5 py-6 sm:px-7 sm:py-7">
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/15 text-2xl font-bold ring-1 ring-white/20 backdrop-blur">
                    {{ mb_substr($user->name, 0, 1) }}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-xl font-bold tracking-tight sm:text-2xl">{{ $user->name }}</div>
                    <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-white/80">
                        <span class="inline-flex items-center rounded-lg bg-white/10 px-2.5 py-0.5 text-xs font-semibold text-white">{{ $roleLabel }}</span>
                        <span class="truncate">{{ $user->email }}</span>
                        @if ($user->phone)
                            <span class="truncate" dir="ltr">{{ $user->phone }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <x-panel-card title="بيانات الحساب" subtitle="الاسم والبريد الإلكتروني المرتبطان بحسابك.">
                <livewire:profile.update-profile-information-form />
            </x-panel-card>

            <x-panel-card title="كلمة المرور" subtitle="استخدم كلمة مرور قوية وغير مستخدمة سابقًا.">
                <livewire:profile.update-password-form />
            </x-panel-card>
        </div>

        @if ($user->hasRole('teacher'))
            <x-panel-card title="البروفايل العام للمدرس" subtitle="ما يظهر للطلاب وأولياء الأمور في الكتالوج وصفحتك العامة.">
                <livewire:teacher.teacher-profile-form />
            </x-panel-card>
        @endif

        <x-panel-card title="منطقة الخطر" subtitle="حذف الحساب نهائي ولا يمكن التراجع عنه." class="border-rose-200/80">
            <livewire:profile.delete-user-form />
        </x-panel-card>
    </x-panel-page>
</x-app-layout>
