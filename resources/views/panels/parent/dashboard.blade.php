<x-app-layout>
    <x-panel-page title="لوحة ولي الأمر" subtitle="اربط أبناءك وتابع اشتراكاتهم ومدفوعاتهم.">
        @php
            $stats = app(\App\Modules\Reports\Services\DashboardReportService::class)->forParent(auth()->user());
        @endphp

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="surface-stat">
                <div class="text-sm text-ink-muted">الأبناء المرتبطون</div>
                <div class="mt-1 text-2xl font-bold text-ink">{{ $stats['children_count'] }}</div>
            </div>
            <div class="surface-stat">
                <div class="text-sm text-ink-muted">إشعارات غير مقروءة</div>
                <div class="mt-1 text-2xl font-bold text-ink">{{ $stats['unread_notifications'] }}</div>
            </div>
        </div>

        <x-panel-card title="ربط ابن بكود الطالب" subtitle="اطلب من ابنك كود الطالب الظاهر في لوحته، ثم أرسل الطلب وانتظر موافقته.">
            <livewire:parent.request-child-link />
        </x-panel-card>

        <x-panel-card title="متابعة الأبناء">
            <div class="space-y-4">
                @forelse ($stats['children'] as $child)
                    <div class="rounded-2xl border border-brand-100 p-4">
                        <div class="mb-3 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div>
                                <div class="font-semibold text-ink">{{ $child['name'] }}</div>
                                <div class="text-sm text-ink-muted">الكود: {{ $child['student_code'] ?? '—' }}</div>
                            </div>
                            <a href="{{ route('parent.children.payments', $child['id']) }}" class="inline-flex items-center rounded-xl bg-brand-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800" wire:navigate>
                                اشتراكات ودفع فودافون
                            </a>
                        </div>
                        <div class="grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-5">
                            <div>
                                <div class="text-ink-muted">اشتراكات نشطة</div>
                                <div class="font-semibold text-ink">{{ $child['active_subscriptions'] }}</div>
                            </div>
                            <div>
                                <div class="text-ink-muted">بانتظار الدفع</div>
                                <div class="font-semibold text-ink">{{ $child['pending_subscriptions'] }}</div>
                            </div>
                            <div>
                                <div class="text-ink-muted">دروس مكتملة</div>
                                <div class="font-semibold text-ink">{{ $child['completed_lessons'] }}</div>
                            </div>
                            <div>
                                <div class="text-ink-muted">مدفوعات معلّقة</div>
                                <div class="font-semibold text-ink">{{ $child['pending_payments'] }}</div>
                            </div>
                            <div>
                                <div class="text-ink-muted">متوسط الامتحانات</div>
                                <div class="font-semibold text-ink">
                                    {{ $child['average_exam_score'] !== null ? $child['average_exam_score'].'%' : '—' }}
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-ink-muted">لا يوجد أبناء مرتبطون بعد. أرسل طلب ربط بكود الطالب.</p>
                @endforelse
            </div>
        </x-panel-card>
    </x-panel-page>
</x-app-layout>
