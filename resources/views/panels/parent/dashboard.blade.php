<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            لوحة ولي الأمر
        </h2>
    </x-slot>

    @php
        $stats = app(\App\Modules\Reports\Services\DashboardReportService::class)->forParent(auth()->user());
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">الأبناء المرتبطون</div>
                    <div class="text-2xl font-semibold mt-1">{{ $stats['children_count'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">إشعارات غير مقروءة</div>
                    <div class="text-2xl font-semibold mt-1">{{ $stats['unread_notifications'] }}</div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b">
                    <h3 class="font-medium">ربط ابن بكود الطالب</h3>
                    <p class="text-sm text-gray-500 mt-1">اطلب من ابنك كود الطالب الظاهر في لوحته، ثم أرسل الطلب وانتظر موافقته.</p>
                </div>
                <div class="p-6">
                    <livewire:parent.request-child-link />
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b">
                    <h3 class="font-medium">متابعة الأبناء</h3>
                </div>
                <div class="p-6 space-y-4">
                    @forelse ($stats['children'] as $child)
                        <div class="border rounded-lg p-4">
                            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between mb-3">
                                <div>
                                    <div class="font-medium text-gray-900">{{ $child['name'] }}</div>
                                    <div class="text-sm text-gray-500">الكود: {{ $child['student_code'] ?? '—' }}</div>
                                </div>
                                <a href="{{ route('parent.children.payments', $child['id']) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500" wire:navigate>
                                    اشتراكات ودفع فودافون
                                </a>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5 text-sm">
                                <div>
                                    <div class="text-gray-500">اشتراكات نشطة</div>
                                    <div class="font-semibold">{{ $child['active_subscriptions'] }}</div>
                                </div>
                                <div>
                                    <div class="text-gray-500">بانتظار الدفع</div>
                                    <div class="font-semibold">{{ $child['pending_subscriptions'] }}</div>
                                </div>
                                <div>
                                    <div class="text-gray-500">دروس مكتملة</div>
                                    <div class="font-semibold">{{ $child['completed_lessons'] }}</div>
                                </div>
                                <div>
                                    <div class="text-gray-500">مدفوعات معلّقة</div>
                                    <div class="font-semibold">{{ $child['pending_payments'] }}</div>
                                </div>
                                <div>
                                    <div class="text-gray-500">متوسط الامتحانات</div>
                                    <div class="font-semibold">
                                        {{ $child['average_exam_score'] !== null ? $child['average_exam_score'].'%' : '—' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">لا يوجد أبناء مرتبطون بعد. أرسل طلب ربط بكود الطالب.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
